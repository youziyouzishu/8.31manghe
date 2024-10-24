<?php

namespace app\queue\redis;

use plugin\admin\app\model\Room;
use plugin\admin\app\model\RoomUsers;
use plugin\admin\app\model\RoomWinprize;
use plugin\admin\app\model\UsersCoupon;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
use Webman\RedisQueue\Client;
use Webman\RedisQueue\Consumer;

class CreateRoom implements Consumer
{
    // 要消费的队列名
    public $queue = 'create-room';

    // 连接名，对应 plugin/webman/redis-queue/redis.php 里的连接`
    public $connection = 'default';

    // 消费
    public function consume($data)
    {
        $room = Room::find($data['id']);
        if ($data['event'] == 'start') {
            if ($room->status == 2) {
                $room->status = 1;
                $room->save();
                $queue = 'create-room';
                Client::send($queue, ['id' => $room->id, 'event' => 'stop'], strtotime($room->end_at) - time());
            }
        } elseif ($data['event'] == 'stop') {
            if ($room->status == 1) {
                $room->status = 3;
                $room->save();
                //开始发奖
                $roomUsersIds = $room->roomUser->pluck('user_id');
                $roomPrizeIds = $room->roomPrize->pluck('user_prize_id');
                // 检查是否有用户和奖品
                if ($roomUsersIds->isEmpty() || $roomPrizeIds->isEmpty()) {
                    return true;
                }


                // 初始化结果数组
                $results = [];
                $unassignedPrizes = [];

                // 随机打乱用户和奖品集合
                $shuffledUsers = $roomUsersIds->shuffle();
                $shuffledPrizes = $roomPrizeIds->shuffle();

                // 确保奖品数量不超过用户数量
                $prizeCount = min($shuffledUsers->count(), $shuffledPrizes->count());

                // 遍历奖品数量，将每个用户与一个奖品配对
                for ($i = 0; $i < $prizeCount; $i++) {
                    $results[] = [
                        'user_id' => $shuffledUsers->get($i),
                        'user_prize_id' => $shuffledPrizes->get($i)
                    ];
                }

                // 列出未被分配的多余奖品
                if ($shuffledPrizes->count() > $prizeCount) {
                    $unassignedPrizes = $shuffledPrizes->slice($prizeCount)->all();
                }

                foreach ($results as $result) {
                    //送奖
                    $user_id = $result['user_id'];
                    $user_prize_id = $result['user_prize_id'];
                    $user_prize = UsersPrize::withTrashed()->find($user_prize_id);
                    $order_user_id = $user_prize->user_id;
                    $user_prize->restore();
                    $user_prize->user_id = $user_id;
                    $user_prize->save();
                    //记录信息
                    RoomWinprize::create([
                        'user_id'=>$user_id,
                        'room_id'=>$room->id,
                        'box_prize_id'=>$user_prize->box_prize_id
                    ]);
                    UsersPrizeLog::create([
                        'user_id' => $user_id,
                        'box_prize_id' => $user_prize->box_prize_id,
                        'mark' => '房间获得'
                    ]);
                    UsersPrizeLog::create([
                        'user_id' => $order_user_id,
                        'box_prize_id' => $user_prize->box_prize_id,
                        'mark' => '房间抽出'
                    ]);
                }
                foreach ($unassignedPrizes as $unassignedPrize){
                    //多余的奖品恢复
                    $user_prize = UsersPrize::withTrashed()->find($unassignedPrize);
                    $user_prize->restore();
                }

            }
        }
    }

}
