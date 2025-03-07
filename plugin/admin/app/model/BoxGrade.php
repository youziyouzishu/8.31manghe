<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id 主键
 * @property int $box_id 盲盒
 * @property int $grade 评级:1=通关赏,2=N级,3=S级,4=SS级,5=SSS级
 * @property int $num 抽奖次数
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxGrade newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxGrade newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BoxGrade query()
 * @mixin \Eloquent
 */
class BoxGrade extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_box_grade';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';


    protected $fillable = [
        'box_id',
        'grade',
        'num',
    ];
    
}
