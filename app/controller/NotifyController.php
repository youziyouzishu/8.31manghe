<?php

namespace app\controller;

use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\BoxOrder;
use support\Db;
use support\exception\BusinessException;
use support\Request;
use Webman\Push\Api;
use Yansongda\Pay\Pay;

class NotifyController extends BaseController
{
    protected array $noNeedLogin = ['*'];


    function pay(Request $request)
    {
        $config = config('pay');


        $pay = Pay::wechat($config);
        try {
            $res = $pay->callback();
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        $res = $res->resource;
        $res = $res['ciphertext'];
        $out_trade_no = $res['out_trade_no'];
        $attach = $res['attach'];


        switch ($attach) {
            case 'box':
                Db::beginTransaction();
                try {
                    $order = BoxOrder::where(['ordersn' => $out_trade_no, 'status' => 1])->first();
                    if (!$order) {
                        return $this->fail('订单不存在');
                    }
                    $order->status = 2;
                    $order->pay_at = date('Y-m-d H:i:s');
                    $order->save();
                    if ($order->coupon){
                        $order->coupon->status = 2;
                        $order->coupon->save();
                    }
                    $winnerPrize = [];
                    //开始执行抽奖操作
                    for ($i = 0; $i < $order->times; $i++) {
                        // 从数据库中获取奖品列表，过滤出数量大于 0 的奖品
                        $prizes = BoxPrize::where([['num','>',0],['box_id' => $order->box_id]])->get();
                        // 如果没有可用奖品，返回提示
                        if ($prizes->isEmpty()) {
                            BoxPrize::query()->update(['num' => DB::raw('total')]);
                            $prizes = BoxPrize::where([['num','>',0],['box_id' => $order->box_id]])->get(); // 重新获取奖品列表
                            if ($prizes->isEmpty()){
                                return $this->fail('没有设置奖池');
                            }
                        }
                        // 计算总概率
                        $totalChance = $prizes->sum('chance');
                        // 生成一个介于 0 和总概率之间的随机数
                        $randomNumber = mt_rand() / mt_getrandmax() * $totalChance;
                        // 累加概率，确定中奖奖品
                        $currentChance = 0.0;
                        foreach ($prizes as $prize) {
                            $currentChance += $prize->chance;
                            if ($randomNumber < $currentChance) {
                                $prize->decrement('num');
                                $winnerPrize[] = $prize;
                                break;
                            }
                        }
                    }
                    DB::commit();
                }catch (\Throwable $e){
                    DB::rollBack();
                    return $this->fail($e->getMessage());
                }

                $api = new Api(
                    'http://127.0.0.1:3232',
                    config('plugin.webman.push.app.app_key'),
                    config('plugin.webman.push.app.app_secret')
                );
                // 给客户端推送私有 prize_draw 事件的消息
                $api->trigger("private-user-{$order->user_id}", 'prize_draw', [
                    'winner_prize' => $winnerPrize
                ]);
                break;
            default:
                return $this->fail('回调错误');
        }

       return $pay->success();
    }

}
