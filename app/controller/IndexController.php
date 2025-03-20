<?php

namespace app\controller;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\model\Box;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\Room;
use plugin\admin\app\model\RoomWinprize;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersCoupon;
use plugin\admin\app\model\UsersDisburse;
use plugin\admin\app\model\UsersGiveLog;
use plugin\admin\app\model\UsersMoneyLog;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
use support\Db;
use support\Log;
use support\Request;
use Webman\Push\Api;
use Webman\RedisQueue\Client;

class IndexController extends BaseController
{
    protected array $noNeedLogin = ['*'];


    function index(Request $request)
    {

        $box_id = 77;
        $level_id = $request->post('level_id', 0);
        $grade = $request->post('grade');

        if (empty($box_id)) {
            return $this->fail('所选盲盒不能为空');
        }


        $item = UsersPrizeLog::find(228464);

        $last = UsersPrizeLog::where(['type' => 0])->whereHas('boxPrize', function ($query) use ($box_id) {
            $query->where('box_id', $box_id);
        })->where('id', '<', $item->id)->orderByDesc('id')->where('grade', $item->grade)->first();


            $prizes = UsersPrizeLog::where(['type' => 0])->whereHas('boxPrize', function ($query) use ($box_id) {
                $query->where('box_id', $box_id);
            })->where('id', '<', $item->id)->where('id', '>', $last->id);


        dump($prizes);


    }
}
