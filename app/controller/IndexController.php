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
use plugin\admin\app\model\UsersDisburse;
use plugin\admin\app\model\UsersGiveLog;
use plugin\admin\app\model\UsersMoneyLog;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
use support\Db;
use support\Log;
use support\Request;
use Webman\Push\Api;

class IndexController extends BaseController
{
    protected array $noNeedLogin = ['*'];


    function index(Request $request)
    {

        $box_id = 78;
        $level_id = 0;
        $grade = 5;
        if (empty($box_id)) {
            return $this->fail('所选盲盒不能为空');
        }


        #取出所有这个盲盒中的SSS中奖记录
        $list = UsersPrizeLog::whereHas('boxPrize', function ($query) use ($box_id,$level_id) {
                        $query->where('box_id', $box_id)->when(!empty($level_id), function (Builder $builder) use ($level_id) {
                            $builder->where('level_id', $level_id);
                        });
                 })
            ->where('type', 0)
            ->where('grade',$grade)
            ->orderBy('id', 'desc')
            ->skip(6)
            ->take(1)
            ->get()
            ->each(function ($item) use ($request, $box_id) {
                $last = UsersPrizeLog::where(['user_id' => $item->user_id, 'type' => 0])
                    ->whereHas('boxPrize', function ($query) use ($box_id) {
                        $query->where('box_id', $box_id);
                    })
                    ->where('id', '<', $item->id)
                    ->orderByDesc('id')
                    ->where('grade', $item->grade)
                    ->first();
                if (!$last) {
                    $prizes = UsersPrizeLog::where(['user_id' => $item->user_id, 'type' => 0])->whereHas('boxPrize', function ($query) use ($box_id) {
                        $query->where('box_id', $box_id);
                    })->where('id', '<', $item->id)->count();
                } else {
                    $prizes = UsersPrizeLog::where(['user_id' => $item->user_id, 'type' => 0])->whereHas('boxPrize', function ($query) use ($box_id) {
                        $query->where('box_id', $box_id);
                    })->where('id', '<', $item->id)->where('id', '>', $last->id)->count();
                    dump($prizes);
                }
                $item->setAttribute('times', $prizes);
            });
        return $this->success('成功', $list);

    }
}
