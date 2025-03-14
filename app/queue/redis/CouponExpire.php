<?php

namespace app\queue\redis;

use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersCoupon;
use Webman\RedisQueue\Client;
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
        $event = $data['event'];
        if ($event == 'user_coupon_expire') {
            $id = $data['id'];
            $user_coupon = UsersCoupon::find($id);
            if ($user_coupon->status == 1){
                $user_coupon->status = 3;
                $user_coupon->save();
            }
        }
    }

}
