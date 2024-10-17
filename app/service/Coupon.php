<?php

namespace app\service;

use plugin\admin\app\model\UserCoupon;
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
            $usercoupon = UserCoupon::where(['id' => $coupon_id])->first();
            if (empty($usercoupon)) {
                throw new BusinessException('优惠券不存在', 1);
            }
            if ($usercoupon->user_id != JwtToken::getUser()->id) {
                throw new BusinessException('优惠券不正确', 1);
            }
            if ($usercoupon->status != 1) {
                throw new BusinessException('优惠券不存在', 1);

            }
            $coupon = $usercoupon->coupon;
            if ($coupon->type == 2 && $coupon->with_amount > $amount) {
                throw new BusinessException('不满足满减条件', 1);
            }
            $coupon_amount = $coupon->amount;
        }
        return $coupon_amount;
    }
}