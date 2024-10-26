<?php

namespace plugin\oplog\app\controller;

use plugin\admin\app\controller\Crud;
use plugin\oplog\app\model\OperationLog;
use support\Request;
use support\Response;

/**
 * 操作日志
 */
class OperationLogController extends Crud
{

    /**
     * @var OperationLog
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new OperationLog;
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('operation-log/index');
    }

    protected function selectInput(Request $request): array
    {
        [$where, $format, $limit, $field, $order, $page] = parent::selectInput($request);

        if (isset($where['operation_log'])) {
            $where['operation_log'] = ['like', "%{$where['operation_log']}%"];
        }

        return [$where, $format, $limit, $field, $order, $page];
    }
}
