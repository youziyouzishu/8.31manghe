<?php

namespace app\controller;

use GuzzleHttp\Client;
use plugin\admin\app\model\Area;
use plugin\admin\app\model\Caiji;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\Room;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersPrize;
use support\Request;

class IndexController extends BaseController
{
    protected array $noNeedLogin = ['*'];
    public function index(Request $request)
    {

        $amount = 0;
        $deliver_amount = Deliver::where('user_id', 1)->withSum('usersPrize','price')->get()->each(function ($item)use(&$amount){
            $amount += $item->users_prize_sum_price;
        });
        return $this->success('',$amount);
    }

}
