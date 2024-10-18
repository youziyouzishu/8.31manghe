<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $box_id 盲盒
 * @property string $image 图片
 * @property integer $name 关卡
 * @method static \Illuminate\Database\Eloquent\Builder|BoxLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxLevel query()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\BoxPrize> $prize
 * @property-read \plugin\admin\app\model\Box|null $box
 * @property-read mixed $name_text
 * @mixin \Eloquent
 */
class BoxLevel extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_box_level';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $appends = ['name_text'];

    function prize()
    {
        return $this->hasMany(BoxPrize::class,'level_id');
    }

    function getNameTextAttribute($value)
    {
        $value = $value ?: ($this->name ?? '');

        return "关卡 $value";
    }

    function box()
    {
        return $this->belongsTo(Box::class,'box_id');
    }

    
}
