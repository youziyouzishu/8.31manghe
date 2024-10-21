<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $name 名称
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsClass newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsClass newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GoodsClass query()
 * @mixin \Eloquent
 */
class GoodsClass extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_goods_class';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
    
}
