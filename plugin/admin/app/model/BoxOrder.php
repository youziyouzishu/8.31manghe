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
 * @property int $user_coupon_id 优惠券
 * @method static \Illuminate\Database\Eloquent\Builder|BoxOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxOrder query()
 * @property-read \plugin\admin\app\model\UsersCoupon|null $userCoupon
 * @property-read \plugin\admin\app\model\Box|null $box
 * @property int $times 抽奖次数
 * @property int $level_id 所属关卡
 * @property-read \plugin\admin\app\model\User|null $user
 * @property int $pay_type 支付类型
 * @property-read \plugin\admin\app\model\BoxLevel|null $level
 * @property int|null $chest_id 所属宝箱
 * @property-read \plugin\admin\app\model\BoxChest|null $chest
 * @property int|null $gaine_id 所属箱子
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
        'times',
        'pay_type',
        'level_id',
        'user_coupon_id',
        'chest_id',
    ];

    function userCoupon()
    {
        return $this->belongsTo(UsersCoupon::class,'user_coupon_id');
    }

    function box()
    {
        return $this->belongsTo(Box::class);
    }

    function chest()
    {
        return $this->belongsTo(BoxChest::class,'chest_id','id');
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }

    function level()
    {
        return $this->belongsTo(BoxLevel::class,'level_id');
    }




}
