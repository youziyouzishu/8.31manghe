<?php

namespace plugin\oplog\app\middleware;

use Chance\Log\facades\OperationLog;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use plugin\oplog\app\model\OperationLog as OperationLogModel;

class AfterOperationLog implements MiddlewareInterface
{

    public function process(Request $request, callable $handler): Response
    {
        /** @var Response $response */
        $response = $handler($request);

        if ($log = OperationLog::getLog()) {
            $ol = new OperationLogModel();
            $ol->username = admin('username');
            $ol->method = $request->method();
            $ol->router = $request->path();
            $ol->ip = $request->getRealIp();
            $ol->request_data = json_encode($request->all(), JSON_UNESCAPED_UNICODE);
            $ol->response_data = $response->rawBody();
            $ol->operation_log = $log;
            $ol->save();
        }
        OperationLog::clearLog();

        return $response;
    }
}