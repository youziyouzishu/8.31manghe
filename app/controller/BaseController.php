<?php

namespace app\controller;

use support\Response;

class BaseController
{
    protected array $noNeedLogin = [];

    protected function json(int $code, string $msg = 'ok', mixed $data = []): Response
    {
        return json(['code' => $code, 'data' => $data, 'msg' => $msg]);
    }

    protected function success(string $msg = '成功', mixed $data = []): Response
    {
        return $this->json(0, $msg, $data);
    }

    protected function fail(string $msg = '失败', mixed $data = []): Response
    {
        return $this->json(1, $msg, $data);
    }
}