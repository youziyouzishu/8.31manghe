<?php

namespace plugin\admin\app\model;

use Illuminate\Database\Eloquent\SoftDeletes;
use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id 主键
 * @property int $chest_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxChestLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxChestLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxChestLog query()
 * @property int $status 状态:0=待支付,1=已支付
 * @property string $ordersn 订单号
 * @mixin \Eloquent
 */
class BoxChestLog extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_box_chest_log';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'chest_id',
        'user_id',
        'status'
    ];

}
