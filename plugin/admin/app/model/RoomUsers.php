<?php

namespace plugin\admin\app\model;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\Pivot;


/**
 * 
 *
 * @property int $id 主键
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder|RoomUsers newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoomUsers newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RoomUsers query()
 * @property int|null $user_id 用户
 * @property int|null $room_id 房间
 * @property-read \plugin\admin\app\model\User|null $user
 * @mixin \Eloquent
 */
class RoomUsers extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_room_users';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['room_id','user_id'];

    protected $connection = 'plugin.admin.mysql';

    /**
     * 格式化日期
     *
     * @return string
     */
    public function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }



}
