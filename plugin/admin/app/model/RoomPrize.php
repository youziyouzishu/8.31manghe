<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int|null $room_id 房间
 * @property int|null $user_prize_id 赏品
 * @method static \Illuminate\Database\Eloquent\Builder|RoomPrize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoomPrize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoomPrize query()
 * @property-read \plugin\admin\app\model\UsersPrize|null $userPrize
 * @property-read \plugin\admin\app\model\Room|null $room
 * @property int $box_prize_id 奖品
 * @property int $num 数量
 * @property int $total 总数量
 * @property string $price 价格
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @mixin \Eloquent
 */
class RoomPrize extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_room_prize';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['room_id','user_prize_id','box_prize_id','num','total','price'];


    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    function userPrize()
    {
        return $this->belongsTo(UsersPrize::class,'user_prize_id');
    }

    function boxPrize()
    {
        return $this->belongsTo(BoxPrize::class,'box_prize_id');
    }



}
