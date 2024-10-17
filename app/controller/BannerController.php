<?php

namespace app\controller;

use Firebase\JWT\JWT;
use plugin\admin\app\model\Banner;
use plugin\admin\app\model\User;
use support\exception\BusinessException;
use support\Request;
use Tinywan\Jwt\JwtToken;
use Webman\RedisQueue\Client;

class BannerController extends BaseController
{
    protected array $noNeedLogin = ['*'];
    #轮播图列表
    public function index(Request $request)
    {
        $rows = Banner::orderBy('id','desc')->get();
        return $this->success('获取成功',$rows);
    }


}
