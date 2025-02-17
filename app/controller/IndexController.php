<?php

namespace app\controller;


use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\model\Room;
use plugin\admin\app\model\UsersDrawLog;
use plugin\admin\app\model\UsersPrizeLog;
use support\Request;

class IndexController extends BaseController
{
    protected array $noNeedLogin = ['*'];

    function test()
    {

            $room_id = 1;
            $row = Room::with([
                'roomPrize' => function ($query) {
                    $query->with(['boxPrize']);
                },
                'user',
                'roomUserUser' => function ($query) {
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

//        $pool_amount = 0;
//        $box_id = 12;
//        $orders = BoxOrder::where('box_id', $box_id)->orderBy('id', 'asc')->get();
//        foreach ($orders as $order){
//            $draw = UsersDrawLog::where('ordersn', $order->ordersn)->first();
//            $draw->prizeLog->each(function (UsersPrizeLog $prize) use (&$pool_amount,$order) {
//
//                $amount = $order->pay_amount / $order->times * (1 - $order->box->rate) - $prize->price;
//                $pool_amount += $amount;
//                dump('ID：'.$prize->id);
//                dump('奖品价格：'.$prize->price);
//                dump('增加奖金池：'.$amount);
//
//                dump('现有奖金池：'.$pool_amount);
//                dump("\n");
//            });
//        }


    }
}
