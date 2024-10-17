<?php

namespace app\exception;

use support\exception\BusinessException;
use Tinywan\Jwt\Exception\JwtRefreshTokenExpiredException;
use Tinywan\Jwt\Exception\JwtTokenException;
use Tinywan\Jwt\Exception\JwtTokenExpiredException;
use Webman\Exception\ExceptionHandler;
use Webman\Http\Request;
use Webman\Http\Response;
use Throwable;

class HandlerException extends ExceptionHandler
{

    public $dontReport = [
        BusinessException::class,
    ];

    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    public function render(Request $request, Throwable $exception): Response
    {
        if ($exception instanceof JwtTokenExpiredException) {
            #token过期重新刷新
            return json([
                'code' => 400,
                'msg' => $exception->getMessage()
            ]);
        }

        if ($exception instanceof JwtTokenException || $exception instanceof JwtRefreshTokenExpiredException) {
            #token无效或刷新失败  重新登录
            return json([
                'code' => 401,
                'msg' => $exception->getMessage()
            ]);
        }


        if (($exception instanceof BusinessException) && ($response = $exception->render($request))) {
            return $response;
        }

        return parent::render($request, $exception);
    }
}