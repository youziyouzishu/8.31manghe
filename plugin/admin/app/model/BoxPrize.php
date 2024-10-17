<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $level 评级
 * @property integer $box_id 所属盲盒
 * @property float $chance 概率
 * @property integer $num 数量
 * @property string $image 图片
 * @property string $name 名称
 * @property int $checkpoint 关卡
 * @property-read \plugin\admin\app\model\Box|null $box
 * @method static \Illuminate\Database\Eloquent\Builder|BoxPrize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxPrize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxPrize query()
 * @property-read mixed $level_text
 * @property int $total 总数量
 * @property int $ticket 通关票:1=是,2=否
 * @mixin \Eloquent
 */
class BoxPrize extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_box_prize';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $appends = ['level_text'];

    function box()
    {
        return $this->belongsTo(Box::class);
    }

    function getLevelTextAttribute($value)
    {
        $value = $value ?: ($this->level ?? '');
        $list = $this->getLevelList();
        return $list[$value] ?? '';
    }

    public function getLevelList()
    {
        return ['1' => '普通', '2' => 'S级', '3' => 'SS级', '4' => 'SSS级'];
    }
    
    
    
}
