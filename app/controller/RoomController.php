<?php

namespace app\controller;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use plugin\admin\app\model\Room;
use plugin\admin\app\model\RoomPrize;
use plugin\admin\app\model\RoomUsers;
use plugin\admin\app\model\RoomWinprize;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersDisburse;
use plugin\admin\app\model\UsersPrize;
use support\Db;
use support\Request;
use Webman\RedisQueue\Client;

class RoomController extends BaseController
{
    function create(Request $request)
    {
        $name = $request->post('name');
        $content = $request->post('content');
        $type = $request->post('type');
        $password = $request->post('password');
        $start_at = $request->post('start_at');
        $end_at = $request->post('end_at');
        $num = $request->post('num');
        $prizes = $request->post('prizes');
        if (empty($prizes)) {
            return $this->fail('奖品不能为空');
        }
        $start_time = strtotime($start_at);
        $end_time = strtotime($end_at);
        if ($start_time >= $end_time) {
            return $this->fail('开始时间不能大于结束时间');
        }

        if ($start_time <= time()) {
            return $this->fail('开始时间不能小于当前时间');
        }
        $roomPrizesData = [];
        foreach ($prizes as $prize) {
            $res = UsersPrize::find($prize['id']);
            if (!$res) {
                return $this->fail('奖品不存在');
            }
            if ($res->safe == 1) {
                return $this->fail('奖品已锁定');
            }
            if ($res->num < $prize['num']) {
                return $this->fail('奖品数量不足');
            }
            $res->decrement('num', $prize['num']);
            if ($res->num <= 0) {
                $res->delete();
            }
            $roomPrizesData[] = ['user_prize_id' => $res->id, 'box_prize_id' => $res->box_prize_id, 'num' => $prize['num'], 'price' => $res->price, 'grade' => $res->grade, 'total' => $prize['num']];
        }

        $room = Room::create([
            'user_id' => $request->uid,
            'name' => $name,
            'content' => $content,
            'type' => $type,
            'password' => $password,
            'start_at' => $start_at,
            'end_at' => $end_at,
            'num' => $num,
        ]);

        // 批量创建关联模型
        $room->roomPrize()->createMany($roomPrizesData);

        //加入队列倒计时开始
        // 队列名
        $queue = 'create-room';
        // 投递延迟消息
        Client::send($queue, ['id' => $room->id, 'event' => 'start'], $start_time - time());

        return $this->success();
    }

    function list(Request $request)
    {
        $status = $request->post('status', 1);
        $rows = Room::with([
            'user',
            'boxPrizes'
        ])
            ->where('status', $status)
            ->paginate()
            ->items();
        return $this->success('成功', $rows);
    }


    function roomDetail(Request $request)
    {
        $room_id = $request->post('room_id');
        $row = Room::with([
            'roomPrize' => function ($query) {
                $query->with(['boxPrize']);
            },
            'user',
            'roomUserUser' => function (HasManyThrough $query) {
                $query->limit(10);
            }])->find($room_id);
        if (empty($row)) {
            return $this->fail('房间不存在');
        }
        $start_time = strtotime($row->start_at);
        $end_time = strtotime($row->end_at);
        $now_time = time();
        if ($row->status == 1) {
            $row->time = $end_time - $now_time;
        }
        if ($row->status == 2) {
            $row->time = $start_time - $now_time;
        }
        return $this->success('成功', $row);
    }

    function roomUsers(Request $request)
    {
        $room_id = $request->post('room_id');
        // 预加载 roomUser 及其关联的 user
        $room = Room::with(['roomUser.user'])
            ->withCount('roomUser')
            ->find($room_id);

        // 提取所有 user 并添加 created_at 字段
        $users = $room->roomUser->map(function (RoomUsers $roomUser) {
            $user = $roomUser->user;
            $user->created_at = $roomUser->created_at;
            return $user;
        });
        // 将 roomUser 数量和用户列表放在同一层
        $result = [
            'users_count' => $room->room_user_count,
            'users' => $users
        ];
        return $this->success('成功', $result);

    }

    function joinRoom(Request $request)
    {
        $room_id = $request->post('room_id');
        $password = $request->post('password');
        $rooms = Room::find($room_id);
        if ($rooms->type == 1 && $rooms->password != $password) {
            return $this->fail('密码错误');
        }
        // 获取本周的开始时间和结束时间
        $startDate = Carbon::now()->startOfWeek(); // 默认一周从周一开始
        $endDate = Carbon::now()->endOfWeek(); // 默认一周到周日结束
        if ($rooms->type == 2 && UsersDisburse::where(['user_id' => $request->uid])->whereBetween('created_at', [$startDate, $endDate])->sum('amount') < 50) {
            return $this->fail('流水不足');
        }
        if (RoomUsers::where(['room_id' => $room_id, 'user_id' => $request->uid])->exists()) {
            return $this->fail('不能重复参与');
        }
        if (RoomUsers::where(['room_id' => $room_id])->count() >= $rooms->num) {
            return $this->fail('房间已满');
        }
        RoomUsers::create([
            'room_id' => $room_id,
            'user_id' => $request->uid,
        ]);
        return $this->success();
    }

    function winList(Request $request)
    {
        $winList = RoomWinprize::with(['room', 'boxPrize'])
            ->where(['user_id' => $request->uid])
            ->paginate()
            ->items();
        return $this->success('成功', $winList);
    }

    function createList(Request $request)
    {
        $rooms = Room::where(['user_id' => $request->uid])
            ->paginate()
            ->getCollection()
            ->each(function ($room) {
                $total = 0;
                $total_price = 0;
                $room->roomPrize->each(function ($item) use (&$total, &$total_price) {
                    $total += $item->total;
                    $total_price += $item->total * $item->price;
                });
                $room->setAttribute('prize_count', $total);
                $room->setAttribute('price', round($total_price, 2));
            });
        return $this->success('成功', $rooms);
    }

    function cancel(Request $request)
    {
        $room_id = $request->post('room_id');
        $room = Room::with('roomPrize.userPrize')->find($room_id);
        if (!$room) {
            return $this->fail('房间不存在');
        }
        if ($room->status != 2) {
            return $this->fail('房间已开奖');
        }
        // 开启事务，确保操作的原子性
        DB::beginTransaction();

        try {
            // 更新房间状态
            $room->status = 3;
            $room->save();
            // 恢复所有关联的用户奖品
            $room->roomPrize->each(function (RoomPrize $roomPrize) {
                if ($res = UsersPrize::where(['user_id' => $roomPrize->room->user_id, 'box_prize_id' => $roomPrize->box_prize_id, 'price' => $roomPrize->price])->first()) {
                    $res->increment('num', $roomPrize->num);
                } else {
                    UsersPrize::create([
                        'user_id' => $roomPrize->room->user_id,
                        'box_prize_id' => $roomPrize->box_prize_id,
                        'room_prize_id' => $roomPrize->id,
                        'num' => $roomPrize->num,
                        'price' => $roomPrize->price,
                        'mark' => '撤销房间恢复奖品',
                        'grade' => $roomPrize->grade,
                    ]);
                }

            });
            // 提交事务
            DB::commit();
        } catch (\Exception $e) {
            // 回滚事务
            DB::rollBack();
            return $this->fail('操作失败: ' . $e->getMessage());
        }
        return $this->success();
    }

    function edit(Request $request)
    {
        $room_id = $request->post('room_id');
        $name = $request->post('name');
        $content = $request->post('content');
        $type = $request->post('type');
        $password = $request->post('password', '');
        $start_at = $request->post('start_at');
        $end_at = $request->post('end_at');
        $num = $request->post('num');
        $prizes = $request->post('prizes');

        // 查找房间
        $room = Room::with('roomPrize.userPrize')->find($room_id);

        if (!$room) {
            return $this->fail('房间不存在');
        }

        // 检查房间状态
        if ($room->status != 2) {
            return $this->fail('房间已开奖，无法编辑');
        }

        // 检查时间范围
        $start_time = strtotime($start_at);
        $end_time = strtotime($end_at);
        if ($start_time >= $end_time) {
            return $this->fail('开始时间不能大于结束时间');
        }

        if ($start_time <= time()) {
            return $this->fail('开始时间不能小于当前时间');
        }

        // 开启事务
        DB::beginTransaction();

        try {
            // 更新房间信息
            $room->update([
                'name' => $name,
                'content' => $content,
                'type' => $type,
                'password' => $password,
                'start_at' => $start_at,
                'end_at' => $end_at,
                'num' => $num,
            ]);
            $room->roomPrize->each(function (RoomPrize $item) {
                //先恢复用户奖品
                if ($res = UsersPrize::where(['user_id' => $item->room->user_id, 'box_prize_id' => $item->box_prize_id, 'price' => $item->price])->first()) {
                    $res->increment('num', $item->num);
                } else {
                    UsersPrize::create([
                        'user_id' => $item->room->user_id,
                        'box_prize_id' => $item->box_prize_id,
                        'num' => $item->num,
                        'price' => $item->price,
                        'mark' => '房间编辑恢复',
                        'grade' => $item->grade,
                    ]);
                }
                //删除房间奖品
                $item->delete();
            });

            $roomPrizes = [];
            foreach ($prizes as $prize) {
                $res = UsersPrize::find($prize['id']);
                if (!$res) {
                    return $this->fail('奖品不存在');
                }
                if ($res->safe == 1) {
                    return $this->fail('奖品已锁定');
                }
                if ($res->num < $prize['num']) {
                    return $this->fail('奖品数量不足');
                }
                $res->decrement('num', $prize['num']);
                if ($res->num <= 0) {
                    $res->delete();
                }
                $roomPrizes[] = [
                    'user_prize_id' => $res->id,
                    'box_prize_id' => $res->box_prize_id,
                    'num' => $prize['num'],
                    'price' => $res->price,
                    'total' => $prize['num']
                ];
            }
            $room->roomPrize()->createMany($roomPrizes);
            // 提交事务
            DB::commit();
        } catch (\Exception $e) {
            // 回滚事务
            DB::rollBack();
            return $this->fail('操作失败: ' . $e->getMessage());
        }

        return $this->success();
    }


}
