<?php

namespace app\controller;


use Carbon\Carbon;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\UsersDisburse;
use plugin\admin\app\model\UsersMoneyLog;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
use support\Db;

class IndexController extends BaseController
{
    protected array $noNeedLogin = ['*'];

    function test()
    {
        $user_id = 975536;
        $profit_created_at = '2025-02-15 00:00:00';
        $profit_end_at = '2025-03-19 23:59:59';
        //线上支付的金额
        $profit_sum_amount = UsersDisburse::where(['user_id' => $user_id])->whereIn('type', [1, 3])->whereBetween('created_at', [$profit_created_at, $profit_end_at])->sum('amount');
        dump('指定时段的线上支付的金额:'.$profit_sum_amount);
        //用户选择发货的赏品价值
        $deliver_amount = 0;
        Deliver::where('user_id', $user_id)->whereIn('status', [1, 2, 3])
            ->whereBetween('created_at',  [$profit_created_at, $profit_end_at])
            ->get()
            ->each(function ($item) use (&$deliver_amount) {
                $deliver_amount += optional($item->userPrize())->withTrashed()->first()->price * $item->num;
            });
        dump('指定时段的用户选择发货的赏品价值:'.$deliver_amount);
        //赠送好友的赏品价值
        $give_amount = UsersPrizeLog::where(['user_id' => $user_id, 'type' => 1])->whereBetween('created_at', [$profit_created_at, $profit_end_at])->selectRaw('SUM(num * price) as total_amount')->value('total_amount') ?? 0;
        dump('指定时段的赠送好友的赏品价值:'.$give_amount);
        //水晶余额
        $money = UsersMoneyLog::where(['user_id' => $user_id])->whereBetween('created_at',  [$profit_created_at, $profit_end_at])->orderByDesc('id')->value('after') ?? 0;
        dump('指定时段的水晶余额:'.$money);
        //赏袋和保险箱剩余商品价值
        $user_prize_sum_price = UsersPrize::where(['user_id' => $user_id])
            ->whereBetween('created_at', [$profit_created_at, $profit_end_at])
            ->select(DB::raw('SUM(price * num) as user_prize_sum_price'))
            ->value('user_prize_sum_price') ?? 0;
        dump('指定时段的赏袋和保险箱剩余商品价值:'.$user_prize_sum_price);
        //活动赠送部分
        $give_prize_price = UsersPrizeLog::where('user_id', $user_id)->where('type', 3)->whereBetween('created_at', [$profit_created_at, $profit_end_at])->selectRaw('SUM(num * price) as total_amount')->value('total_amount') ?? 0;
        dump('指定时段的活动赠送部分:'.$give_prize_price);
        //系统增加的水晶
        $system_money = UsersMoneyLog::where(['user_id' => $user_id,'memo' => '活动赠送'])->whereBetween('created_at', [$profit_created_at, $profit_end_at])->sum('money') ?? 0;
        dump('指定时段的系统增加的水晶:'.$system_money);
        $profit = round($profit_sum_amount - $deliver_amount - $give_amount - $money - $user_prize_sum_price - $give_prize_price - $system_money, 2);
        dump($profit);

    }
}
