<?php

namespace plugin\admin\app\model;

use Illuminate\Database\Eloquent\SoftDeletes;
use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $prize_id 奖品
 * @property integer $class_id 分类
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @method static \Illuminate\Database\Eloquent\Builder|Goods newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Goods newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Goods query()
 * @property string|null $content 详情
 * @property \Illuminate\Support\Carbon|null $deleted_at 删除时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Goods onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Goods withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Goods withoutTrashed()
 * @mixin \Eloquent
 */
class Goods extends Base
{
    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_goods';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    function boxPrize()
    {
        return $this->belongsTo(BoxPrize::class, 'prize_id');
    }
    
}
