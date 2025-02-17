<?php

namespace plugin\admin\app\model;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use plugin\admin\app\model\Base;
use support\Request;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $user_id 用户
 * @property integer $box_prize_id 奖品
 * @property integer $num 数量
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize query()
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @property int $safe 保险箱
 * @property int $grade 评级:1=通关赏,2=N级,3=S级,4=SS级,5=SSS级
 * @property string $mark 备注
 * @property-read \plugin\admin\app\model\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize withoutTrashed()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\RoomPrize> $roomPrizes
 * @property string $price 参考价
 * @property \Illuminate\Support\Carbon|null $deleted_at 删除时间
 * @mixin \Eloquent
 */
class UsersPrize extends Pivot
{
    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users_prize';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['id', 'user_id', 'box_prize_id', 'safe', 'mark' , 'price','num','grade'];

    /**
     * 格式化日期
     *
     * @return string
     */
    public function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public static function getUserPresentLevelTicketCount($level_box_id,$level_name,$user_id)
    {

        $getLastLevel = BoxLevel::getLastLevel($level_box_id, $level_name);
        if ($getLastLevel) {
            $lastPrizes = $getLastLevel->boxPrize()->where(['grade' => 1])->pluck('id');//获取上一关通关券
            return self::where(['user_id' => $user_id])->whereIn('box_prize_id', $lastPrizes)->sum('num');//获取用户拥有的上一关通关券
        }else{
            return 0;
        }
    }

    function boxPrize()
    {
        return $this->belongsTo(BoxPrize::class,'box_prize_id');
    }

    public function roomPrizes()
    {
        return $this->hasMany(RoomPrize::class);
    }

    function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }



    
}
