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
            }

        }

        return $handler($request);
    }
    
}
