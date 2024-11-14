<?php

namespace app\queue\redis;

use plugin\admin\app\model\Room;
use plugin\admin\app\model\RoomPrize;
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
            dump('房间'.$data['id'].'创建开始计时');
            if ($room->status == 2) {
                dump('房间'.$data['id'].'停止开始计时');
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
                $roomUsers = $room->roomUser;
                // 检查是否有用户和奖品
                if ($roomUsers->isEmpty()) {
                    return true;
                }
                // 获取所有剩余数量大于0的奖品

                $roomUsers->each(function (RoomUsers $roomUser)use($room){
                    $prizes = $room->roomPrize()->where('num', '>', 0)->get();
                    if ($prizes->isEmpty()) {
                        return; // 奖品已抽完，停止分配
                    }
                    $prize = $prizes->random(); // 选择数量最多的奖品
                    $prize->decrement('num');
                    //记录信息
                    RoomWinprize::create([
                        'user_id'=>$roomUser->user_id,
                        'room_id'=>$room->id,
                        'box_prize_id'=>$prize->box_prize_id
                    ]);
                    UsersPrizeLog::create([
                        'user_id' => $roomUser->user_id,
                        'box_prize_id' => $prize->box_prize_id,
                        'mark' => '房间获得',
                        'type'=>7,
                        'price'=>$prize->price,
                        'grade' => $prize->boxPrize->grade,
                    ]);
                    UsersPrizeLog::create([
                        'user_id' => $room->user_id,
                        'box_prize_id' => $prize->box_prize_id,
                        'mark' => '房间抽出',
                        'type'=>8,
                        'price'=>$prize->price,
                        'grade' => $prize->boxPrize->grade,
                    ]);
                    if ($res = UsersPrize::where(['user_id' => $roomUser->user_id, 'box_prize_id' => $prize->box_prize_id,'price'=>$prize->price])->first()){
                        $res->increment('num');
                    }else{
                        UsersPrize::create([
                            'user_id' => $roomUser->user_id,
                            'box_prize_id' => $prize->box_prize_id,
                            'price'=>$prize->price,
                            'num'=>1,
                            'mark' => '房间获得'
                        ]);
                    }
                });

                //恢复多余的奖品
                $room->roomPrize()->where('num', '>', 0)->get()->each(function (RoomPrize $roomPrize){
                    if ($res = UsersPrize::where(['user_id' => $roomPrize->room->user_id, 'box_prize_id' => $roomPrize->box_prize_id,'price'=>$roomPrize->price])->first()){
                        $res->increment('num',$res->num);
                    }else{
                        UsersPrize::create([
                            'user_id' => $roomPrize->room->user_id,
                            'box_prize_id' => $roomPrize->box_prize_id,
                            'price'=>$roomPrize->price,
                            'num'=>$roomPrize->num,
                            'mark'=>'房间抽奖完成剩余'
                        ]);
                    }
                });

            }
        }
    }

}
