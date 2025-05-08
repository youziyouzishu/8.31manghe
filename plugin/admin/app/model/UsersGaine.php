<?php

namespace plugin\admin\app\model;

use Illuminate\Database\Eloquent\SoftDeletes;
use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property int $id 主键
 * @property int $user_id 用户
 * @property int $gaine_id 盒子
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property string|null $deleted_at 删除时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersGaine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersGaine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersGaine query()
 * @property-read \plugin\admin\app\model\BoxGaine|null $gaine
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersGaine onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersGaine withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersGaine withoutTrashed()
 * @property int $draw_id 抽奖id
 * @mixin \Eloquent
 */
class UsersGaine extends Base
{

    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users_gaine';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'gaine_id',
        'draw_id'
    ];

    function gaine()
    {
        return $this->belongsTo(BoxGaine::class, 'gaine_id', 'id');
    }
    
    
    
}
