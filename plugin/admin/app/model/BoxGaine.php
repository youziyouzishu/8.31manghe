<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property integer $box_id 盲盒
 * @property string $image 封面
 * @property string $chance 概率
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $name 名称
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxGaine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxGaine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxGaine query()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\BoxPrize> $boxPrize
 * @property-read \plugin\admin\app\model\Box|null $box
 * @mixin \Eloquent
 */
class BoxGaine extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_box_gaine';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    function boxPrize()
    {
        return $this->hasMany(BoxPrize::class,'gaine_id','id');
    }

    function box()
    {
        return $this->belongsTo(Box::class,'box_id','id');
    }
    
    
    
}
