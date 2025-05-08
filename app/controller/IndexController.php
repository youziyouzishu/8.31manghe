<?php

namespace app\controller;


use Carbon\Carbon;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\UsersDisburse;
use support\Request;

class IndexController extends BaseController
{
    protected array $noNeedLogin = ['*'];

    function index()
    {
        $order = BoxOrder::find(44167);
        $order->box()->increment('kol_consume_amount');
    }



}
