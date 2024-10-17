<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $address 地址
 * @property string $name 名称
 * @property string $qid 唯一标识
 * @property string $tel 手机号
 * @method static \Illuminate\Database\Eloquent\Builder|Caiji newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Caiji newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Caiji query()
 * @mixin \Eloquent
 */
class Caiji extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_caiji';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    

    protected $fillable = ['address','name','qid','tel'];
    
}
