<?php

namespace app\controller;

use Carbon\Carbon;
use plugin\admin\app\model\Coupon;
use plugin\admin\app\model\UsersCoupon;
use support\Request;
use Webman\RedisQueue\Client;

class CouponController extends BaseController
{

    protected array $noNeedLogin = [];


    function index(Request $request)
    {
        $coupon_id = UsersCoupon::where(['user_id' => $request->user_id])->pluck('coupon_id');
        $rows = Coupon::where('num', '>', 0)
            ->where('fuli',0)
            ->where('status',1)
            ->whereNotIn('id', $coupon_id)
            ->get();

        return $this->success('成功', $rows);
    }

    function receive(Request $request)
    {
        $coupon_id = UsersCoupon::where(['user_id' => $request->user_id])->pluck('coupon_id');
        $rows = Coupon::where('num', '>', 0)
            ->where('fuli',0)
            ->whereNotIn('id', $coupon_id)
            ->get();
        foreach ($rows as $row){
            $row->decrement('num');

            $expired_at = Carbon::now()->addDays($row->expired_day);

            $user_coupon = UsersCoupon::create([
                'user_id' => $request->user_id,
                'coupon_id' => $row->id,
                'name'=>$row->name,
                'type'=>$row->type,
                'amount'=>$row->amount,
                'with_amount'=>$row->with_amount,
                'expired_at'=>$expired_at->toDateTimeString(),
            ]);
            Client::send('coupon-expire',['event'=>'user_coupon_expire','id'=>$user_coupon->id],$expired_at->timestamp - time());
        }
        return $this->success('领取成功');
    }

}
