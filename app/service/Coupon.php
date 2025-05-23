<?php

namespace app\service;

use plugin\admin\app\model\UsersCoupon;
use support\exception\BusinessException;
use Tinywan\Jwt\JwtToken;

class Coupon
{
    /**
     * @throws BusinessException
     */
    public static function getCouponAmount($amount,$coupon_id)
    {
        $coupon_amount = 0;
        if (!empty($coupon_id)) {
            $usercoupon = UsersCoupon::where(['id' => $coupon_id])->first();
            if (empty($usercoupon)) {
                throw new BusinessException('优惠券不存在', 1);
            }
            if ($usercoupon->status != 1) {
                throw new BusinessException('优惠券不存在', 1);
            }
            if ($usercoupon->type == 2 && $usercoupon->with_amount > $amount) {
                throw new BusinessException('不满足满减条件', 1);
            }
            $coupon_amount = $usercoupon->amount;
        }
        return $coupon_amount;
    }
}