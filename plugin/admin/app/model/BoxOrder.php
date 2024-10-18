<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $user_id 用户
 * @property integer $status 状态
 * @property integer $box_id 所属盲盒
 * @property string $amount 订单金额
 * @property string $pay_amount 支付金额
 * @property string $coupon_amount 优惠金额
 * @property string $ordersn 订单编号
 * @property string $pay_at 支付时间
 * @property int $coupon_id 优惠券
 * @method static \Illuminate\Database\Eloquent\Builder|BoxOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxOrder query()
 * @property-read \plugin\admin\app\model\UsersCoupon|null $coupon
 * @property-read \plugin\admin\app\model\Box|null $box
 * @property int $times 抽奖次数
 * @property int $level_id 所属关卡
 * @mixin \Eloquent
 */
class BoxOrder extends Base
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_box_orders';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'created_at',
        'updated_at',
        'user_id',
        'status',
        'box_id',
        'amount',
        'pay_amount',
        'coupon_amount',
        'ordersn',
        'pay_at',
        'coupon_id',
        'times'
    ];

    function coupon()
    {
        return $this->hasOne(UsersCoupon::class, 'id', 'coupon_id');
    }

    function box()
    {
        return $this->belongsTo(Box::class);
    }


}
