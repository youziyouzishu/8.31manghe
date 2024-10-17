<?php

namespace app\controller;

use Carbon\Carbon;
use plugin\admin\app\model\Coupon;
use plugin\admin\app\model\UserCoupon;
use support\Request;

class CouponController extends BaseController
{

    protected array $noNeedLogin = [];


    function index(Request $request)
    {
        $coupon_id = UserCoupon::where(['user_id' => $request->uid])->distinct()->pluck('coupon_id');

        $rows = Coupon::where('num', '>', 0)
            ->where('expire_at', '>', Carbon::now()->format('Y-m-d H:i:s'))
            ->whereNotIn('id', $coupon_id)
            ->get();

        return $this->success('成功', $rows);
    }

    function receive(Request $request)
    {
        $coupon_id = UserCoupon::where(['user_id' => $request->uid])->distinct()->pluck('coupon_id');

        $rows = Coupon::where('num', '>', 0)
            ->where('expire_at', '>', Carbon::now()->format('Y-m-d H:i:s'))
            ->whereNotIn('id', $coupon_id)
            ->get();
        foreach ($rows as $row){
            $row->num -= 1;
            $row->save();

            UserCoupon::create([
                'user_id' => $request->uid,
                'coupon_id' => $row->id,
            ]);
        }
        return $this->success('领取成功');
    }

}
