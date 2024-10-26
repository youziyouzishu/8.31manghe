<?php

namespace app\controller;

use GuzzleHttp\Client;
use plugin\admin\app\model\Area;
use plugin\admin\app\model\Caiji;
use support\Request;

class IndexController extends BaseController
{
    protected array $noNeedLogin = ['*'];
    public function index(Request $request)
    {


        $randomNumber = mt_rand() / mt_getrandmax() * 0.03;
        dump($randomNumber);

        return $this->success();
    }

}
