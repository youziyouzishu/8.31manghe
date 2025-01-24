<?php
namespace app\middleware;

use ReflectionClass;
use Tinywan\Jwt\Exception\JwtRefreshTokenExpiredException;
use Tinywan\Jwt\Exception\JwtTokenException;
use Tinywan\Jwt\Exception\JwtTokenExpiredException;
use Tinywan\Jwt\JwtToken;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class AuthCheck implements MiddlewareInterface
{
    public function process(Request $request, callable $handler) : Response
    {

        // 通过反射获取控制器哪些方法不需要登录
        if (!empty($request->controller)){  #路由中return无实际controller
            $controller = new ReflectionClass($request->controller);
            $noNeedLogin = $controller->getDefaultProperties()['noNeedLogin'] ?? [];
            $arr = array_map('strtolower', $noNeedLogin);
            // 是否存在

            if (!in_array(strtolower($request->action), $arr) && !in_array('*', $arr)) {

                // 访问的方法需要登录
                $request->uid = JwtToken::getCurrentId();
                dump($request);
                dump('用户ID');
                dump(JwtToken::getCurrentId());
            }
        }
        // 如果是options请求则返回一个空响应，否则继续向洋葱芯穿越，并得到一个响应
        $response = $request->method() == 'OPTIONS' ? response('') : $handler($request);
        // 给响应添加跨域相关的http头
        $response->withHeaders([
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Origin' => $request->header('origin', '*'),
            'Access-Control-Allow-Methods' => $request->header('access-control-request-method', '*'),
            'Access-Control-Allow-Headers' => $request->header('access-control-request-headers', '*'),
        ]);
        return $response;
    }
    
}
