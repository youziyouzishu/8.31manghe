<?php

namespace app\controller;

use app\service\Pay;
use GuzzleHttp\Client;
use plugin\admin\app\model\Area;
use plugin\admin\app\model\Caiji;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\Room;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersPrize;
use support\Request;
use Wolfcode\PhpLogviewer\webman\laravel\LogViewer;

class IndexController extends BaseController
{
    protected array $noNeedLogin = ['*'];
    public function index(Request $request)
    {
        $res = Pay::refund(1,0.01,'20241106672ADDC80E02F','20241106672ADDC80E022','申请退款');
        dump($res);
    }

    function log()
    {
        return (new \Wolfcode\PhpLogviewer\webman\laravel\LogViewer())->fetch();
    }

}
