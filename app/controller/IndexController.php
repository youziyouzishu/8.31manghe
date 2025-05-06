<?php

namespace app\controller;


use Carbon\Carbon;
use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\UsersDisburse;
use support\Request;

class IndexController extends BaseController
{
    protected array $noNeedLogin = ['*'];

    function index()
    {
        $a = [3,4];
        dump(array_random_keys($a));
    }



}
