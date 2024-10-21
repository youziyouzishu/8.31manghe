<?php

namespace plugin\admin\app\model;

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
 * @property integer $prize_id 奖品
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize query()
 * @property-read \plugin\admin\app\model\BoxPrize|null $prize
 * @property int $safe 保险箱
 * @property string $mark 备注
 * @property-read \plugin\admin\app\model\User|null $user
 * @property \Illuminate\Support\Carbon $deleted_at 删除时间
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrize withoutTrashed()
 * @mixin \Eloquent
 */
class UsersPrize extends Base
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

    protected $fillable = ['id', 'user_id', 'prize_id'];

    public static function getUserPresentLevelTicketCount($level_box_id,$level_name,$user_id)
    {

        $getLastLevel = BoxLevel::getLastLevel($level_box_id, $level_name);
        if ($getLastLevel) {
            $lastPrizes = $getLastLevel->prize()->where(['grade' => 1])->pluck('id');//获取上一关通关券
            return self::where(['user_id' => $user_id])->whereIn('prize_id', $lastPrizes)->get()->count();//获取用户拥有的上一关通关券
        }else{
            return 0;
        }
    }

    function prize()
    {
        return $this->belongsTo(BoxPrize::class,'prize_id');
    }

    function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }
    
}
