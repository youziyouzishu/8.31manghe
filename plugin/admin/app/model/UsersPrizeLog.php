<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $user_id 用户
 * @property integer $source_user_id 来源对象
 * @property integer $box_prize_id 奖品
 * @property integer $type 类型
 * @property integer $grade 评级:1=通关赏,2=N级,3=S级,4=SS级,5=SSS级
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrizeLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrizeLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UsersPrizeLog query()
 * @property string $mark 备注
 * @property-read \plugin\admin\app\model\BoxPrize|null $boxPrize
 * @property int $draw_id 抽奖
 * @property int $num 数量
 * @property-read \plugin\admin\app\model\User|null $sourceUser
 * @property string $price 参考价
 * @property-read \plugin\admin\app\model\User|null $user
 * @mixin \Eloquent
 */
class UsersPrizeLog extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users_prize_log';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['user_id','box_prize_id','mark','price','draw_id','type','grade','num'];

    function boxPrize()
    {
        return $this->belongsTo(BoxPrize::class,'box_prize_id','id');
    }

    function sourceUser()
    {
        return $this->belongsTo(User::class,'source_user_id','id');
    }

    function user()
    {
        return $this->belongsTo(User::class);
    }

    
}
