<?php

namespace app\controller;

use Carbon\Carbon;
use plugin\admin\app\model\Coupon;
use plugin\admin\app\model\UsersCoupon;
use support\Request;

class CouponController extends BaseController
{

    protected array $noNeedLogin = [];


    function index(Request $request)
    {
        $coupon_id = UsersCoupon::where(['user_id' => $request->user_id])->pluck('coupon_id');

        $rows = Coupon::where('num', '>', 0)
            ->where('expire_at', '>', Carbon::now()->format('Y-m-d H:i:s'))
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
            ->where('expire_at', '>', Carbon::now()->format('Y-m-d H:i:s'))
            ->where('fuli',0)
            ->whereNotIn('id', $coupon_id)
            ->get();
        foreach ($rows as $row){
            $row->decrement('num');
            UsersCoupon::create([
                'user_id' => $request->user_id,
                'coupon_id' => $row->id,
            ]);
        }
        return $this->success('领取成功');
    }

}
