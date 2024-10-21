<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id 主键
 * @property int|null $user_id 用户
 * @property string $name 房间名称
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property string|null $start_at 开始时间
 * @property string|null $end_at 结束时间
 * @property string $content 活动介绍
 * @property int $type 房间类型:1=密码,2=流水
 * @property string $password
 * @property int $bill_type 流水类型:1=本周>=50,2=今日>=10
 * @property int $status 房间状态:1=进行中,2=未开始,3=已结束
 * @property int $num 参与人数
 * @method static \Illuminate\Database\Eloquent\Builder|Room newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Room newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Room query()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\RoomPrize> $roomPrize
 * @property-read mixed $status_text
 * @property-read \plugin\admin\app\model\User|null $user
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

    protected $fillable = ['name','content','type','password','bill_type','status','num','user_id'];

    protected $appends = ['status_text'];
    function roomPrize()
    {
        return $this->hasMany(RoomPrize::class,'room_id','id');
    }

    function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }


    function getStatusTextAttribute($value)
    {
        $value = $value ?: ($this->status ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    public function getStatusList()
    {
        return ['1' => '进行中', '2' => '未开始', '3' => '已结束'];
    }

}
