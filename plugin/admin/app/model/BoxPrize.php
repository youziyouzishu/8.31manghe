<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $grade 评级
 * @property integer $box_id 所属盲盒
 * @property float $chance 概率
 * @property integer $num 数量
 * @property string $image 图片
 * @property string $name 名称
 * @property-read \plugin\admin\app\model\Box|null $box
 * @method static \Illuminate\Database\Eloquent\Builder|BoxPrize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxPrize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxPrize query()
 * @property-read mixed $grade_text
 * @property int $total 总数量
 * @property int $level_id 所属关卡
 * @property-read \plugin\admin\app\model\BoxLevel|null $level
 * @property string $price 市场价
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\UsersPrize> $userPrizes
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

    protected $appends = ['grade_text'];

    function box()
    {
        return $this->belongsTo(Box::class);
    }

    function getGradeTextAttribute($value)
    {
        $value = $value ?: ($this->grade ?? '');
        $list = $this->getGradeList();
        return $list[$value] ?? '';
    }

    public function getGradeList()
    {
        return ['1' => '通关赏', '2' => 'N级', '3' => 'S级', '4' => 'SS级', '5' => 'SSS级'];
    }

    function level()
    {
        return $this->belongsTo(BoxLevel::class,'level_id');
    }

    public function userPrizes()
    {
        return $this->hasMany(UsersPrize::class);
    }
    
    
    
}
