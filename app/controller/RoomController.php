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
        $user_prize_ids = $request->post('user_prize_ids');
        $user_prize_ids = explode(',', $user_prize_ids);
        if (empty($user_prize_ids)) {
            return $this->fail('奖品不能为空');
        }
        $user_prizes = UsersPrize::where(['user_id' => $request->uid])->whereIn('id', $user_prize_ids)->get();
        if ($user_prizes->isEmpty()) {
            return $this->fail('奖品不存在');
        }
        $start_time = strtotime($start_at);
        $end_time = strtotime($end_at);
        if ($start_time >= $end_time) {
            return $this->fail('开始时间不能大于结束时间');
        }

        if ($start_time <= time()) {
            return $this->fail('开始时间不能小于当前时间');
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
        // 使用 each 方法批量创建关联模型

        $user_prizes->each(function (UsersPrize $user_prize) use ($room) {
            $room->roomPrize()->create(['user_prize_id' => $user_prize->id, 'box_prize_id' => $user_prize->box_prize_id]);
            //软删除用户奖品  取消时恢复
            $user_prize->delete();
        });

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
            ->withCount(['roomPrize'])
            ->where('status', $status)
            ->paginate()
            ->items();

        return $this->success('成功', $rows);
    }


    function roomDetail(Request $request)
    {
        $room_id = $request->post('room_id');
        $row = Room::with([
            'userPrize' => function ($query) {
                $query->with(['boxPrize'])->withTrashed();
            },
            'user',
            'roomUserUser' => function (HasManyThrough $query) {
                $query->limit(10);
            }])->find($room_id);
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
            ->transform(function (Room $room) {
                $count = $room->roomPrize->count();
                $price = $room->roomPrize->sum('price');
                $room->prize_count = $count;
                $room->price = $price;
                return $room;
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
                $roomPrize->userPrize->restore();
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
        $user_prize_ids = $request->post('user_prize_ids');
        $user_prize_ids = collect(explode(',', $user_prize_ids));
        // 查找房间
        $room = Room::with('roomPrize.userPrize')->find($room_id);

        if (!$room) {
            return $this->fail('房间不存在');
        }

        // 检查房间状态
        if ($room->status != 2) {
            return $this->fail('房间已开奖，无法编辑');
        }

        // 处理奖品 ID 列表
        $user_prize_ids = collect(explode(',', $user_prize_ids));
        if ($user_prize_ids->isEmpty()) {
            return $this->fail('奖品不能为空');
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

            // 获取当前房间的所有奖品 ID
            $current_user_prize_ids = $room->roomPrize->pluck('user_prize_id');

            // 计算需要删除的奖品 ID
            $to_delete = $current_user_prize_ids->diff($user_prize_ids);

            // 计算需要新增的奖品 ID
            $to_add = $user_prize_ids->diff($current_user_prize_ids);

            // 删除旧的奖品关联并恢复用户奖品
            $to_delete->each(function ($user_prize_id) use ($room) {
                $room->roomPrize()->where('user_prize_id', $user_prize_id)->delete();
                UsersPrize::where('id', $user_prize_id)->restore();
            });
            // 创建新的奖品关联并软删除用户奖品
            $to_add->each(function ($user_prize_id) use ($room) {
                $room->roomPrize()->create(['user_prize_id' => $user_prize_id]);
                UsersPrize::where('id', $user_prize_id)->delete();
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


}
