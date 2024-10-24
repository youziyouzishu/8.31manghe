<?php

namespace plugin\admin\app\model;



/**
 * 
 *
 * @property int $id 主键
 * @property int $user_id 用户
 * @property string $ordersn 订单编号
 * @property int $big_prize_id 大奖
 * @property int $small_prize_id 小奖
 * @property int $status 状态:1=未支付,2=已支付
 * @property string|null $pay_at 支付时间
 * @property string $profit 盈亏
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $times 次数
 * @method static \Illuminate\Database\Eloquent\Builder|DreamOrders newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DreamOrders newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DreamOrders query()
 * @property-read \plugin\admin\app\model\BoxPrize|null $bigPrize
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\DreamOrdersPrize> $orderPrize
 * @property-read \plugin\admin\app\model\BoxPrize|null $smallPrize
 * @property string $probability 概率
 * @property string $pay_amount 支付金额
 * @property-read \plugin\admin\app\model\User|null $user
 * @mixin \Eloquent
 */
class DreamOrders extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_dream_orders';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['user_id','ordersn','big_prize_id','small_prize_id','status','pay_at','profit','times','probability','pay_amount'];
    function bigPrize()
    {
        return $this->belongsTo(BoxPrize::class,'big_prize_id');
    }

    function smallPrize()
    {
        return $this->belongsTo(BoxPrize::class,'small_prize_id');
    }

    function orderPrize()
    {
        return $this->hasMany(DreamOrdersPrize::class,'order_id');
    }

    function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

}
