<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id ID(主键)
 * @property string $admin_id 管理员id
 * @property string $role_id 角色id
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AdminRole query()
 * @mixin \Eloquent
 */
class AdminRole extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_admin_roles';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
    public $timestamps = false;
    
}
