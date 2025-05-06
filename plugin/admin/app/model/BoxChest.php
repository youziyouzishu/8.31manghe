<?php

namespace plugin\admin\app\model;

use Illuminate\Database\Eloquent\SoftDeletes;
use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id 主键
 * @property int|null $box_id
 * @property int|null $index
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property \Illuminate\Support\Carbon|null $deleted_at 删除时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxChest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxChest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxChest onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxChest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxChest withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxChest withoutTrashed()
 * @property int|null $num 剩余数量
 * @property int|null $total 总数量
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\BoxOrder> $orders
 * @mixin \Eloquent
 */
class BoxChest extends Base
{
    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_box_chest';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'box_id',
        'index',
    ];

    function orders()
    {
      return $this->hasMany(BoxOrder::class, 'chest_id', 'id');
    }



}
