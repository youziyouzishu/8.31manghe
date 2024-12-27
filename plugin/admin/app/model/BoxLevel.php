<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $box_id 盲盒
 * @property string $image 图片
 * @property integer $name 关卡
 * @method static \Illuminate\Database\Eloquent\Builder|BoxLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BoxLevel query()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \plugin\admin\app\model\BoxPrize> $boxPrize
 * @property-read \plugin\admin\app\model\Box|null $box
 * @property-read mixed $name_text
 * @property string $detail_img 详情图片
 * @mixin \Eloquent
 */
class BoxLevel extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_box_level';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $appends = ['name_text'];

    function boxPrize()
    {
        return $this->hasMany(BoxPrize::class,'level_id');
    }

    function getNameTextAttribute($value)
    {
        $value = $value ?: ($this->name ?? '');
        return "关卡 $value";
    }

    function box()
    {
        return $this->belongsTo(Box::class,'box_id');
    }

    #找出当前关卡的上一关
    public static function getLastLevel($box_id,$name)
    {
        return self::where(['box_id' => $box_id, ['name','<',$name]])->orderBy('name', 'desc')->first();
    }

    #找到当前关卡的下一关
    public static function getNextLevel($box_id,$name)
    {
        return self::where(['box_id' => $box_id, ['name','>',$name]])->orderBy('name')->first();
    }

    #找到当前盲盒的第一关
    public static function getFirstLevel($box_id){
        return self::where(['box_id' => $box_id])->orderBy('name')->first();
    }

    
}
