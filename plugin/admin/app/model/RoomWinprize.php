<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id 主键
 * @property int $user_id 用户
 * @property int $room_id 房间
 * @property int $box_prize_id 奖品
 * @property int $room_prize_id 房间奖品ID
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @property-read \plugin\admin\app\model\Room|null $room
 * @property-read \plugin\admin\app\model\RoomPrize|null $roomPrize
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoomWinprize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoomWinprize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoomWinprize query()
 * @mixin \Eloquent
 */
class RoomWinprize extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_room_winprize';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['user_id','room_id','box_prize_id','room_prize_id'];


    function room()
    {
        return $this->belongsTo(Room::class,'room_id');
    }

    function boxPrize()
    {
        return $this->belongsTo(BoxPrize::class,'box_prize_id');
    }

    function roomPrize()
    {
        return $this->belongsTo(RoomPrize::class,'room_prize_id');
    }





}
