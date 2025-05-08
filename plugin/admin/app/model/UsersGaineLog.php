<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property int $id 主键
 * @property int $user_id 用户
 * @property int $gaine_id 盒子
 * @property string|null $mark 备注
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersGaineLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersGaineLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersGaineLog query()
 * @mixin \Eloquent
 */
class UsersGaineLog extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users_gaine_log';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'gaine_id',
        'mark',
    ];
    
    
    
}
