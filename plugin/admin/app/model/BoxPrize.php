<?php

namespace plugin\admin\app\model;

use Illuminate\Database\Eloquent\SoftDeletes;
use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $grade 评级
 * @property integer $box_id 所属盲盒
 * @property float $chance 概率
 * @property string $image 图片
 * @property string $name 名称
 * @property-read \plugin\admin\app\model\Box|null $box
 * @method static \Illuminate\Database\Eloquent\Builder|BoxPrize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxPrize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxPrize query()
 * @property-read mixed $grade_text
 * @property int $level_id 所属关卡
 * @property string $price 实际价格
 * @property string $show_price 展示价格
 * @property-read \plugin\admin\app\model\BoxLevel|null $level
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\UsersPrize> $userPrizes
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property \Illuminate\Support\Carbon|null $deleted_at 删除时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxPrize onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxPrize withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxPrize withoutTrashed()
 * @property int|null $num 奖品数量
 * @mixin \Eloquent
 */
class BoxPrize extends Base
{
    use SoftDeletes;
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

    protected $appends = ['grade_text','chance'];

    function box()
    {
        return $this->belongsTo(Box::class);
    }

    function getChanceAttribute($value)
    {
        $value = $value ?: ($this->chance ?? '');

        $formattedNumber = number_format($value, 3, '.', '');
        // 去除末尾的零和小数点
        return rtrim(rtrim($formattedNumber, '0'), '.');
    }

    function getGradeTextAttribute($value)
    {
        $value = $value ?: ($this->grade ?? '');
        $list = $this->getGradeList();
        return $list[$value] ?? '';
    }

    public static function getGradeList()
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
