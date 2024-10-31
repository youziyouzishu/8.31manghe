<?php

namespace app\controller;



use Illuminate\Support\Facades\Redirect;
use plugin\admin\app\model\Banner;
use plugin\admin\app\model\UsersDisburse;
use plugin\admin\app\model\UsersPrize;
use support\Request;
use Webman\Route\Route;


class BannerController extends BaseController
{
    protected array $noNeedLogin = ['index'];

    #轮播图列表
    public function index(Request $request)
    {
        $param = $request->post();

        $request->set('post', ['bbb'=>2]);
        dump($request->post());
        $rows = Banner::orderBy('id','desc')->get();
        return $this->success('获取成功',$rows);
    }




}
