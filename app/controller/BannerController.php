<?php

namespace app\controller;

use plugin\admin\app\model\Banner;
use support\Request;

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
