<?php

namespace app\controller;



use app\service\Pay;
use plugin\admin\app\model\Banner;
use support\Request;


class BannerController extends BaseController
{
    protected array $noNeedLogin = ['index'];

    #轮播图列表
    public function index(Request $request)
    {
        $ret = Pay::pay(0.01, '11111111', '123123', 'goods');
        dump(json_decode($ret));
        $rows = Banner::orderBy('id','desc')->get();
        return $this->success('获取成功',$rows);
    }




}
