<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $name 券名称
 * @property integer $type 券类型
 * @property string $amount 优惠金额
 * @property string $with_amount 满足金额
 * @property integer $num 券数量
 * @property string $mark 备注
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Coupon query()
 * @property-read mixed $type_text
 * @property int $status 状态
 * @property int $fuli 是否福利:0=否,1=是
 * @mixin \Eloquent
 */
class Coupon extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_coupon';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $appends = ['type_text'];

    function getTypeTextAttribute($value)
    {
        $value = $value ?: ($this->type ?? '');
        $list = $this->getTypeList();
        return $list[$value] ?? '';
    }

    public function getTypeList()
    {
        return ['1' => '无门槛', '2' => '满减'];
    }



}
