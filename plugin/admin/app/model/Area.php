<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id ID
 * @property int|null $pid 父id
 * @property string|null $shortname 简称
 * @property string|null $name 名称
 * @property string|null $mergename 全称
 * @property int|null $level 层级:1=省,2=市,3=区/县
 * @property string|null $pinyin 拼音
 * @property string|null $code 长途区号
 * @property string|null $zip 邮编
 * @property string|null $first 首字母
 * @property string|null $lng 经度
 * @property string|null $lat 纬度
 * @property string|null $city_code 城市编码
 * @property int|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|Area newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Area newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Area query()
 * @property int $pass 采集过
 * @mixin \Eloquent
 */
class Area extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_area';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    

    protected $fillable = ['pid','shortname','name','mergename','level','pinyin','code','zip','first','lng','lat','city_code','pass'];
    
}
