<?php

namespace plugin\admin\app\model;



/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int|null $user_id 用户
 * @property int|null $coupon_id 优惠券
 * @property int $status 状态:1=未使用,2=已使用,3=已过期
 * @method static \Illuminate\Database\Eloquent\Builder|UsersCoupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersCoupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersCoupon query()
 * @property-read \plugin\admin\app\model\Coupon $coupon
 * @property-read mixed $status_text
 * @property string $name 券名称
 * @property int $type 券类型:1=无门槛,2=满减
 * @property string $amount 优惠金额
 * @property string $with_amount 满足金额
 * @property \Illuminate\Support\Carbon|null $expired_at 过期时间
 * @mixin \Eloquent
 */
class UsersCoupon extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users_coupon';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'coupon_id',
        'status',
        'expired_at',
        'name',
        'type',
        'amount',
        'with_amount',
    ];

    protected $appends = ['status_text'];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    function getStatusTextAttribute($value)
    {
        $value = $value ?: ($this->status ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    public function getStatusList()
    {
        return ['1' => '未使用', '2' => '已使用' , '3'=> '已过期'];
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }


}
