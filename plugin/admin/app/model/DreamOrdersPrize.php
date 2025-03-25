<?php

namespace plugin\admin\app\model;



/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $order_id 订单
 * @property int $box_prize_id 奖品
 * @property int $type 奖品类型:0=小奖,1=大奖
 * @method static \Illuminate\Database\Eloquent\Builder|DreamOrdersPrize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DreamOrdersPrize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DreamOrdersPrize query()
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @property-read \plugin\admin\app\model\DreamOrders|null $orders
 * @property string $price 市场价
 * @property int $grade 评级:1=通关赏,2=N级,3=S级,4=SS级,5=SSS级
 * @mixin \Eloquent
 */
class DreamOrdersPrize extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_dream_orders_prize';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['order_id','box_prize_id','type','price','grade'];

    function boxPrize()
    {
        return $this->belongsTo(BoxPrize::class, 'box_prize_id');
    }

    function orders()
    {
        return $this->belongsTo(DreamOrders::class, 'order_id');
    }


}
