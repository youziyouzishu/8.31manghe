<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

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
