<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $user_id 用户
 * @property int $status 状态:0=待支付,1=待发货,2=待收货,3=已完成
 * @property string $pay_amount 运费
 * @property string $ordersn 订单编号
 * @property string $waybill 快递单号
 * @property string $express 快递公司
 * @method static \Illuminate\Database\Eloquent\Builder|Deliver newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deliver newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deliver query()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\DeliverDetail> $detail
 * @property int $address_id 收货地址
 * @property-read \plugin\admin\app\model\Address|null $address
 * @property-read mixed $status_text
 * @property string $mark 备注
 * @property int $pay_type 支付方式:0=无,1=支付宝,2=水晶,3=云闪付
 * @property string|null $pay_time 付款时间
 * @property string|null $complete_time 收货时间
 * @property-read \plugin\admin\app\model\User|null $user
 * @property int $box_prize_id 奖品
 * @property int $user_prize_id 所属用户奖品
 * @property int $num 数量
 * @property string $price 市场价
 * @property int $grade 评级:1=通关赏,2=N级,3=S级,4=SS级,5=SSS级
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @property-read \plugin\admin\app\model\UsersPrize|null $userPrize
 * @mixin \Eloquent
 */
class Deliver extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_deliver';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'status',
        'pay_amount',
        'ordersn',
        'waybill',
        'express',
        'address_id',
        'pay_type',
        'mark',
        'pay_time',
        'complete_time',
        'box_prize_id',
        'user_prize_id',
        'num',
        'price',
        'grade',
    ];

    protected $appends = ['status_text'];

    function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    function getStatusTextAttribute($value)
    {
        $value = $value ?: ($this->status ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    public function getStatusList()
    {
        return ['1' => '待发货', '2' => '待收货', '3' => '完成', '4'=>'取消发货'];
    }

    function boxPrize()
    {
        return $this->belongsTo(BoxPrize::class,'box_prize_id','id');
    }

    function userPrize()
    {
        return $this->belongsTo(UsersPrize::class,'user_prize_id','id');
    }


    function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


}
