<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property integer $type 分类
 * @property string $images 图片
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $name 名称
 * @property string $price 单价
 * @method static \Illuminate\Database\Eloquent\Builder|Box newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Box newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Box query()
 * @property-read mixed $images_text
 * @property-read mixed $type_text
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\BoxPrize> $boxPrize
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\BoxLevel> $level
 * @property string $image 封面
 * @property int $status 状态
 * @property int $weigh 权重
 * @property string $consume_amount 消费金额
 * @property string $pool_amount 奖金池
 * @property string $rate 毛利率
 * @mixin \Eloquent
 */
class Box extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_box';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $appends = ['images_text','type_text'];

    public function boxPrize()
    {
        return $this->hasMany(BoxPrize::class);
    }

    function getImagesTextAttribute($value)
    {
        $value = $value ?: ($this->images ?? '');
        return explode(',', $value);
    }

    function getTypeTextAttribute($value)
    {
        $value = $value ?: ($this->type ?? '');
        $list = $this->getTypeList();
        return $list[$value] ?? '';
    }

    public function getTypeList()
    {
        return ['1' => '福利赏', '2' => '高爆赏', '3' => '无限赏', '4' => '闯关赏'];
    }

    function level()
    {
        return $this->hasMany(BoxLevel::class,'box_id');
    }



}
