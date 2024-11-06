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
 * @property int $times 次数
 * @method static \Illuminate\Database\Eloquent\Builder|UsersDrawLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersDrawLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersDrawLog query()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\UsersPrizeLog> $prizeLog
 * @property int $box_id 所属盲盒
 * @property int $level_id 所属关卡
 * @property-read \plugin\admin\app\model\Box|null $box
 * @property string $ordersn 订单编号
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\GoodsOrder> $orders
 * @mixin \Eloquent
 */
class UsersDrawLog extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users_draw_log';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['user_id', 'times', 'box_id', 'level_id','ordersn'];

    function prizeLog()
    {
        return $this->hasMany(UsersPrizeLog::class, 'draw_id', 'id');
    }

    function box()
    {
        return $this->belongsTo(Box::class, 'box_id', 'id');
    }

    function orders()
    {
        return $this->belongsTo(BoxOrder::class, 'ordersn', 'ordersn');
    }


}
