<?php

namespace app\controller;

use GuzzleHttp\Client;
use plugin\admin\app\model\Area;
use plugin\admin\app\model\Caiji;
use plugin\admin\app\model\Room;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersPrize;
use support\Request;

class IndexController extends BaseController
{
    protected array $noNeedLogin = ['*'];
    public function index(Request $request)
    {


        $room = UsersPrize::find(4);
        return $this->success('',$room->box);
    }

}
