<?php

namespace plugin\admin\app\model;


/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $user_id 用户
 * @property string $amount 金额
 * @property string $mark 备注
 * @method static \Illuminate\Database\Eloquent\Builder|UsersDisburse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersDisburse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersDisburse query()
 * @property int $type 支付类型:1=支付宝,2=水晶,3=云闪付
 * @property int $scene 场景:1=盲盒抽奖,2=商城购买,3=DIY抽奖
 * @property-read \plugin\admin\app\model\User|null $user
 * @mixin \Eloquent
 */
class UsersDisburse extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users_disburse';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    protected $fillable = ['user_id', 'amount','mark','type','scene'];

    function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


}
