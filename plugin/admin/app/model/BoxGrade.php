<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;


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
