<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property int $user_id
 * @property int $room_id
 * @property int $box_prize_id
 * @method static \Illuminate\Database\Eloquent\Builder|RoomWinprize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoomWinprize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoomWinprize query()
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @property-read \plugin\admin\app\model\Room|null $room
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

    protected $fillable = ['user_id','room_id','box_prize_id'];


    function room()
    {
        return $this->belongsTo(Room::class,'room_id');
    }

    function boxPrize()
    {
        return $this->belongsTo(BoxPrize::class,'box_prize_id');
    }





}
