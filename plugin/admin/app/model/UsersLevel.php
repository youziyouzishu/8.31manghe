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
 * @property int $level_id 所在关卡
 * @method static \Illuminate\Database\Eloquent\Builder|UsersLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersLevel query()
 * @property int $box_id 所属盲盒
 * @mixin \Eloquent
 */
class UsersLevel extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users_level';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['user_id', 'level_id', 'box_id'];
    
    
}
