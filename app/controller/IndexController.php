<?php

namespace app\controller;

use support\Request;
use Webman\Push\Api;

class IndexController extends BaseController
{
    protected array $noNeedLogin = ['*'];
    public function index(Request $request)
    {
        $path= '';
            if (false === $path) {
                dump(111);
            }else{
                dump(2222);
            }

    }

}
