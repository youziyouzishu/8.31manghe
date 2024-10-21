<?php

namespace app\controller;



use Illuminate\Support\Facades\Redirect;
use plugin\admin\app\model\Banner;
use support\Request;
use Webman\Route\Route;


class BannerController extends BaseController
{
    protected array $noNeedLogin = ['*'];

    #轮播图列表
    public function index()
    {
        $rows = Banner::orderBy('id','desc')->get();
        return $this->success('获取成功',$rows);
    }




}
