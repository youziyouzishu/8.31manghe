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

    protected $fillable = ['room_id','user_prize_id'];


    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    function userPrize()
    {
        return $this->belongsTo(UsersPrize::class,'user_prize_id');
    }



}
