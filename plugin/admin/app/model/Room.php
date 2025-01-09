<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id 主键
 * @property int|null $user_id 用户
 * @property string $name 房间名称
 * @property string $content 活动介绍
 * @property int $type 房间类型
 * @property string $password 密码
 * @property int $status 房间状态:1=进行中,2=未开始,3=已结束
 * @property int $num 最大参与人数
 * @property \Illuminate\Support\Carbon|null $start_at 开始时间
 * @property \Illuminate\Support\Carbon|null $end_at 结束时间
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\BoxPrize> $boxPrizes
 * @property-read mixed $status_text
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\RoomPrize> $roomPrize
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\RoomUsers> $roomUser
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\User> $roomUserUser
 * @property-read \plugin\admin\app\model\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\UsersPrize> $userPrize
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Room query()
 * @mixin \Eloquent
 */
class Room extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_room';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['name','content','type','password','status','num','user_id','start_at','end_at'];


    /**
     * 获取应该转换的属性。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime:Y-m-d H:i:s',
            'end_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    protected $appends = ['status_text'];


    public function boxPrizes()
    {
        return $this->hasManyThrough(BoxPrize::class, RoomPrize::class, 'room_id', 'id', 'id', 'box_prize_id');
    }

    function userPrize()
    {
        return $this->hasManyThrough(UsersPrize::class, RoomPrize::class, 'room_id', 'id','id','user_prize_id');
    }

    function roomPrize()
    {
        return $this->hasMany(RoomPrize::class);
    }


    function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function roomUserUser()
    {
        return $this->hasManyThrough(User::class, RoomUsers::class, 'room_id', 'id', 'id', 'user_id');
    }



    function getStatusTextAttribute($value)
    {
        $value = $value ?: ($this->status ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    function roomUser()
    {
        return $this->hasMany(RoomUsers::class);
    }

    public function getStatusList()
    {
        return ['1' => '进行中', '2' => '未开始', '3' => '已结束'];
    }

}
