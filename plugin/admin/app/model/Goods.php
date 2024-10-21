<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $prize_id 奖品
 * @property integer $class_id 分类
 * @property-read \plugin\admin\app\model\BoxPrize|null $prize
 * @method static \Illuminate\Database\Eloquent\Builder|Goods newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Goods newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Goods query()
 * @mixin \Eloquent
 */
class Goods extends Base
{
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

    function prize()
    {
        return $this->belongsTo(BoxPrize::class, 'prize_id');
    }
    
}
