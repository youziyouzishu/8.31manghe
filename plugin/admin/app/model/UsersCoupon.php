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

    protected $fillable = ['user_id', 'coupon_id', 'status'];

    protected $appends = ['status_text'];


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
