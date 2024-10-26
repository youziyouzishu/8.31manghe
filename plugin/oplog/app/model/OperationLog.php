<?php

namespace plugin\oplog\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id (主键)
 * @property string $username 用户名
 * @property string $method 请求方式
 * @property string $router 路由
 * @property string $ip IP
 * @property string $request_data 请求数据
 * @property string $response_data 响应数据
 * @property mixed $operation_log 操作日志
 * @property string $created_at 创建时间
 */
class OperationLog extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'oplog_operation_log';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    
    public const UPDATED_AT = null;

    // 不生成该表的日志
    public $doNotRecordLog = true;
}
