<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $deliver_id 发货
 * @property int $prize_id 奖品
 * @method static \Illuminate\Database\Eloquent\Builder|DeliverDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DeliverDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DeliverDetail query()
 * @property int $user_prize_id 所属用户奖品
 * @property-read \plugin\admin\app\model\Deliver|null $deliver
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @property-read \plugin\admin\app\model\UsersPrize|null $userPrize
 * @mixin \Eloquent
 */
class DeliverDetail extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_deliver_detail';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';


    protected $fillable = ['deliver_id', 'prize_id'];

    function deliver()
    {
        return $this->belongsTo(Deliver::class, 'deliver_id');
    }

    function boxPrize()
    {
        return $this->belongsTo(BoxPrize::class, 'prize_id');
    }

    function userPrize()
    {
        return $this->hasOne(UsersPrize::class, 'user_prize_id');
    }
    
}
