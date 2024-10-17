<?php

namespace app\queue\redis;

use plugin\admin\app\model\User;
use plugin\admin\app\model\UserCoupon;
use Webman\RedisQueue\Consumer;

class CouponExpire implements Consumer
{
    // 要消费的队列名
    public $queue = 'coupon-expire';

    // 连接名，对应 plugin/webman/redis-queue/redis.php 里的连接`
    public $connection = 'default';

    // 消费
    public function consume($data)
    {
        #优惠券过期
        $events = UserCoupon::where(['coupon_id' => $data['id'], 'status' => 1])->get();
        foreach ($events as $event) {
            $event->status = 3;
            $event->save();
        }
    }

}
