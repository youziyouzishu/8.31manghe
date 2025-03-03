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
use plugin\admin\app\model\UsersGiveLog;
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
        for ($i = 1; $i <= 200; $i++){
            $rows = BoxPrize::where('box_id', 60)->inRandomOrder()->limit(10)->get();
            $winnerPrize = ['gt_n' => 0, 'list' => $rows];
            $api = new Api(
                'http://127.0.0.1:3232',
                config('plugin.webman.push.app.app_key'),
                config('plugin.webman.push.app.app_secret')
            );
            // 给客户端推送私有 prize_draw 事件的消息
            $api->trigger('private-user-975558', 'prize_draw', [
                'winner_prize' => $winnerPrize
            ]);
        }

        return $this->success('成功');
    }
}
