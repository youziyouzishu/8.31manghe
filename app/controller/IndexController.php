<?php

namespace app\controller;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\Room;
use plugin\admin\app\model\RoomWinprize;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersDisburse;
use plugin\admin\app\model\UsersMoneyLog;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
use support\Db;
use support\Log;
use Webman\Push\Api;

class IndexController extends BaseController
{
    protected array $noNeedLogin = ['*'];



    function index()
    {

    }
}
