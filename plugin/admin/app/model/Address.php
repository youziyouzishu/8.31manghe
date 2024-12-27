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
 * @property int $default 默认
 * @property string $detail 详细地址
 * @property string $province 省
 * @property string $city 市
 * @property string $region 区
 * @property string $mobile 手机号
 * @property string $name 姓名
 * @method static \Illuminate\Database\Eloquent\Builder|Address newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Address newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Address query()
 * @property-read \plugin\admin\app\model\User|null $user
 * @mixin \Eloquent
 */
class Address extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_address';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['user_id','default','detail','province','city','region','mobile','name'];

    function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

}
