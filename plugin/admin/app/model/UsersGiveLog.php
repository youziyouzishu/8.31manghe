<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property int $id 主键
 * @property int $user_id 用户
 * @property int $to_user_id 赠送对象
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\UsersPrizeLog> $giveLog
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\UsersPrizeLog> $receiveLog
 * @property-read \plugin\admin\app\model\User|null $toUser
 * @property-read \plugin\admin\app\model\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersGiveLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersGiveLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersGiveLog query()
 * @mixin \Eloquent
 */
class UsersGiveLog extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users_give_log';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'to_user_id'
    ];

    function giveLog()
    {
        return $this->hasMany(UsersPrizeLog::class, 'draw_id', 'id')->where('type',1);
    }

    function receiveLog()
    {
        return $this->hasMany(UsersPrizeLog::class, 'draw_id', 'id')->where('type',2);
    }

    function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id', 'id');
    }
    
    
    
}
