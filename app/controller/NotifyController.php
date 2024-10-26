<?php

namespace app\controller;

use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\DeliverDetail;
use plugin\admin\app\model\DreamOrders;
use plugin\admin\app\model\GoodsOrder;
use plugin\admin\app\model\UsersDisburse;
use plugin\admin\app\model\UsersDrawLog;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
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
        $paytype = $request->input('paytype');

        $pay = Pay::wechat(config('payment'));
        if ($paytype == 'wechat') {
            try {
                $res = $pay->callback();
            } catch (\Throwable $e) {
                return $this->fail($e->getMessage());
            }
            $res = $res->resource;
            $res = $res['ciphertext'];
            $out_trade_no = $res['out_trade_no'];
            $attach = $res['attach'];
        } elseif ($paytype == 'balance') {
            $out_trade_no = $paytype->input('out_trade_no');
            $attach = $paytype->input('attach');
        } else {
            return $this->fail('支付方式错误');
        }

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
                    if ($order->userCoupon) {
                        $order->userCoupon->status = 2;
                        $order->userCoupon->save();
                    }

                    //开始执行抽奖操作
                    $draw = UsersDrawLog::create([
                        'times' => $order->times,
                        'box_id' => $order->box_id,
                        'level_id' => $order->level_id,
                        'ordersn' => $out_trade_no,
                    ]); #创建抽奖记录

                    $winnerPrize = [];
                    for ($i = 0; $i < $order->times; $i++) {
                        // 从数据库中获取奖品列表，过滤出数量大于 0 的奖品
                        $prizes = BoxPrize::where([['num', '>', 0]])
                            ->where(['box_id' => $order->box_id])
                            ->get();
                        // 如果没有可用奖品，返回提示
                        if ($prizes->isEmpty()) {
                            BoxPrize::query()->update(['num' => DB::raw('total')]);
                            $prizes = BoxPrize::where([['num', '>', 0]])
                                ->where(['box_id' => $order->box_id])
                                ->get(); // 重新获取奖品列表
                            if ($prizes->isEmpty()) {
                                return $this->fail('没有设置奖池');
                            }
                        }

                        // 计算总概率
                        $totalChance = $prizes->sum('chance');
                        // 生成一个介于 0 和总概率之间的随机数
                        $randomNumber = mt_rand() / mt_getrandmax() * $totalChance;

                        // 累加概率，确定中奖奖品
                        $currentChance = 0.0;
                        //达人拥有额外的中奖率
                        if ($order->user->kol == 0){
                            $currentChance += $order->user->chance;
                        }

                        foreach ($prizes as $prize) {
                            $currentChance += $prize->chance;
                            if ($randomNumber < $currentChance) {
                                //达人抽奖不减数量
                                if ($order->user->kol == 0){
                                    $prize->decrement('num');
                                }
                                $winnerPrize[] = $prize;
                                // 发放奖品并且记录
                                UsersPrize::create([
                                    'user_id' => $order->user_id,
                                    'box_prize_id' => $prize->id,
                                    'mark' => '抽奖获得'
                                ]);

                                UsersPrizeLog::create([
                                    'draw_id' => $draw->id,
                                    'user_id' => $order->user_id,
                                    'box_prize_id' => $prize->id,
                                    'mark' => '抽奖获得',
                                ]);
                                break;
                            }
                        }
                    }
                    DB::commit();
                } catch (\Throwable $e) {
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

                if ($paytype == 'wechat'){
                    UsersDisburse::create([
                        'user_id' => $order->user_id,
                        'amount' => $order->pay_amount,
                        'mark' => '抽奖'
                    ]);
                }

                break;
            case 'goods':
                $order = GoodsOrder::where(['ordersn' => $out_trade_no, 'status' => 1])->first();
                if (!$order) {
                    return $this->fail('订单不存在');
                }
                $order->status = 2;
                $order->pay_at = date('Y-m-d H:i:s');
                $order->save();
                //给用户发放赏袋
                UsersPrize::create([
                    'user_id' => $order->user_id,
                    'box_prize_id' => $order->goods->prize_id,
                    'mark' => '购买商品获得'
                ]);
                UsersPrizeLog::create([
                    'user_id' => $order->user_id,
                    'box_prize_id' => $order->goods->prize_id,
                    'mark' => '购买商品获得'
                ]);
                if ($paytype == 'wechat'){
                    UsersDisburse::create([
                        'user_id' => $order->user_id,
                        'amount' => $order->pay_amount,
                        'mark' => '购买商品'
                    ]);
                }
                break;
            case 'freight':
                $order = Deliver::where(['ordersn' => $out_trade_no, 'status' => 0])->first();
                if (!$order) {
                    return $this->fail('订单不存在');
                }
                $order->status = 1;
                $order->save();
                $order->detail->each(function (DeliverDetail $item){
                    //支付成功  删除用户的奖品
                    UsersPrizeLog::create([
                        'user_id' => $item->userPrize->user_id,
                        'box_prize_id' => $item->prize_id,
                        'mark' => '发货成功，删除奖品'
                    ]);
                    $item->userPrize()->delete();
                });

                if ($paytype == 'wechat'){
                    UsersDisburse::create([
                        'user_id' => $order->user_id,
                        'amount' => $order->freight,
                        'mark' => '支付运费'
                    ]);
                }
                break;
            case 'dream':
                $order = DreamOrders::where(['ordersn' => $out_trade_no, 'status' => 1])->first();
                if (!$order) {
                    return $this->fail('订单不存在');
                }
                $order->status = 2;
                $order->pay_at = date('Y-m-d H:i:s');

                $probability = $order->probability;
                $big_prize_id = $order->big_prize_id;
                $small_prize_id = $order->small_prize_id;

                // 生成奖品ID数组并创建订单奖品记录
                $prize_ids = collect();
                for ($i = 0; $i < $order->times; $i++) {
                    $is_big_prize = mt_rand(1, 100) <= $probability;
                    $prize_id = $is_big_prize ? $big_prize_id : $small_prize_id;
                    $prize_ids->push($prize_id);

                    $order->orderPrize()->create(['box_prize_id' => $prize_id]);

                    UsersPrize::create([
                        'user_id' => $order->user_id,
                        'box_prize_id' => $prize_id,
                        'mark' => '梦想DIY抽奖获得'
                    ]);
                    UsersPrizeLog::create([
                        'user_id' => $order->user_id,
                        'box_prize_id' => $prize_id,
                        'mark' => '梦想DIY抽奖获得'
                    ]);
                }

                // 查询奖品信息
                $prizes = BoxPrize::whereIn('id', $prize_ids)->get()->keyBy('id');

                // 计算利润
                $order->profit = $order->pay_amount - $prizes->sum('price');

                // 保存订单信息
                $order->save();

                // 构建最终结果数组，保留重复的条目
                $winnerPrize = $prize_ids->map(function ($prize_id) use ($prizes) {
                    return $prizes[$prize_id];
                })->all();

                // 初始化API客户端
                $api = new Api(
                    'http://127.0.0.1:3232',
                    config('plugin.webman.push.app.app_key'),
                    config('plugin.webman.push.app.app_secret')
                );

                // 给客户端推送私有 prize_draw 事件的消息
                $api->trigger("private-user-{$order->user_id}", 'prize_draw', [
                    'winner_prize' => $winnerPrize
                ]);

                // 处理微信支付的额外逻辑
                if ($paytype == 'wechat') {
                    UsersDisburse::create([
                        'user_id' => $order->user_id,
                        'amount' => $order->pay_amount,
                        'mark' => '梦想DIY抽奖'
                    ]);
                }
                break;
            default:
                return $this->fail('回调错误');
        }

        switch ($paytype) {
            case 'wechat':
                $pay->success();
                break;
            case 'balance':
                return true;
            default:
                return $this->fail('支付类型错误');
        }
    }

}
