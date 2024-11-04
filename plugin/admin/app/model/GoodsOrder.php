<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int|null $user_id 用户
 * @property int|null $goods_id 商品
 * @property string $ordersn 订单编号
 * @property-read \plugin\admin\app\model\Goods|null $goods
 * @property-read \plugin\admin\app\model\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsOrder query()
 * @property string $pay_amount 支付金额
 * @property int $status 订单状态:1=未支付,2=已支付
 * @property string $amount 订单金额
 * @property string $pay_at 支付时间
 * @property int $pay_type 支付类型
 * @mixin \Eloquent
 */
class GoodsOrder extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_goods_orders';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['user_id','goods_id','ordersn','pay_amount','status','amount'];

    function goods()
    {
        return $this->belongsTo(Goods::class, 'goods_id');
    }

    function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }



}
