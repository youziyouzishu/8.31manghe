<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $user_id 用户
 * @property integer $prize_id 奖品
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize query()
 * @mixin \Eloquent
 */
class UsersPrize extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users_prize';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
    
}
