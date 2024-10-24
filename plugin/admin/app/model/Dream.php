<?php

namespace plugin\admin\app\model;


/**
 * 
 *
 * @property int $id 主键
 * @property int $box_prize_id 奖品
 * @property int $type 类型:1=梦想大奖,2=基础奖
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder|Dream newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Dream newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Dream query()
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @mixin \Eloquent
 */
class Dream extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_dream';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['box_prize_id','type'];

    function boxPrize()
    {
        return $this->belongsTo(BoxPrize::class);
    }

}
