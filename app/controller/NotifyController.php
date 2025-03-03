<?php

namespace app\controller;

use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\DreamOrders;
use plugin\admin\app\model\GoodsOrder;
use plugin\admin\app\model\UsersDisburse;
use plugin\admin\app\model\UsersDrawLog;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
use support\Cache;
use support\Log;
use support\Request;
use Webman\Push\Api;
use Yansongda\Pay\Pay;

class NotifyController extends BaseController
{
    protected array $noNeedLogin = ['*'];


    function alipay(Request $request)
    {
        $request->set('get', ['paytype' => 'alipay']);
        try {
            $this->pay($request);
        } catch (\Throwable $e) {
            return response($e->getMessage());
        }
        return response('success');
    }

    function wechat(Request $request)
    {
        $request->set('get', ['paytype' => 'wechat']);
        try {
            $this->pay($request);
        } catch (\Throwable $e) {
            return json(['code' => 'FAIL', 'message' => $e->getMessage()]);
        }
        return json(['code' => 'SUCCESS', 'message' => '成功']);
    }

    function balance(Request $request)
    {
        $request->set('get', ['paytype' => 'balance']);
        try {
            $this->pay($request);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success();
    }

    #云闪付
    function unipay(Request $request)
    {
        $request->set('get', ['paytype' => 'unipay']);
        try {
            $this->pay($request);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success();
    }

    /**
     * @throws \Exception
     */
    private function pay(Request $request)
    {

        try {
            $paytype = $request->input('paytype');


            if ($paytype == 'wechat') {
                $pay = Pay::wechat(config('payment'));
                $res = $pay->callback($request->post());
                $res = $res->resource;
                $res = $res['ciphertext'];
                $out_trade_no = $res['out_trade_no'];
                $attach = $res['attach'];
                $mchid = $res['mchid'];
                $transaction_id = $res['transaction_id'];
                $openid = $res['payer']['openid'] ?? '';
            } elseif ($paytype == 'balance') {
                $out_trade_no = $request->input('out_trade_no');
                $attach = $request->input('attach');
            } elseif ($paytype == 'alipay' || $paytype == 'unipay') {
                $ret = $request->all();
                $data = json_decode($ret['resp_data']);
                if ($data->trans_stat == 'F') {
                    throw new \Exception($data->bank_message);
                }
                $attach = $data->remark;
                $out_trade_no = $data->mer_ord_id;
            } else {
                throw new \Exception('支付类型错误');
            }

            switch ($attach) {
                case 'box':
                    Log::info('抽奖了');
                    $order = BoxOrder::where(['ordersn' => $out_trade_no, 'status' => 1])->first();
                    if (!$order) {
                        throw new \Exception('订单不存在');
                    }
                    $order->status = 2;
                    $order->pay_type = $paytype == 'alipay' ? 1 : ($paytype == 'balance' ? 2 : 3);
                    $order->pay_at = date('Y-m-d H:i:s');
                    $order->save();
                    if ($order->userCoupon) {
                        $order->userCoupon->status = 2;
                        $order->userCoupon->save();
                    }
                    if ($order->user->new == 1 && ($paytype == 'unipay' || $paytype == 'alipay')) {
                        $order->user->new = 0;
                        $order->user->new_time = date('Y-m-d H:i:s');
                        $order->user->save();
                    }
                    #增加盲盒消费金额
                    if ($order->user->kol == 0) {
                        $order->box->increment('consume_amount', $order->pay_amount);
                    }else{
                        $order->box->increment('kol_consume_amount', $order->pay_amount);
                    }
                    //开始执行抽奖操作
                    $draw = UsersDrawLog::create([
                        'user_id' => $order->user_id,
                        'times' => $order->times,
                        'box_id' => $order->box_id,
                        'level_id' => $order->level_id,
                        'ordersn' => $out_trade_no,
                    ]); #创建抽奖记录
                    $winnerPrize = ['gt_n' => 0, 'list' => []];
                    for ($i = 0; $i < $order->times; $i++) {
                        //每次循环都刷新盲盒
                        $order->refresh();

                        // 从数据库中获取奖品列表
                        $prizes = BoxPrize::where(['box_id' => $order->box_id])
                            ->when(!empty($order->level_id), function (Builder $query) use ($order) {
                                $query->where('level_id', $order->level_id);
                            }, function (Builder $query) use ($order) {
                                //如果是普通用户才受奖金池限制
                                if ($order->user->kol == 0) {
                                    $query->whereBetween('price', [0, $order->box->pool_amount]);
                                }else{
                                    $query->whereBetween('price', [0, $order->box->kol_pool_amount]);
                                }
                            })
                            ->inRandomOrder()
                            ->get();

                        // 如果没有可用奖品，返回提示
                        if ($prizes->isEmpty()) {
                            if (!empty($order->level_id)) {
                                throw new \Exception('闯关赏没有奖品');
                            } else {
                                $prizes = BoxPrize::where(['box_id' => $order->box_id])
                                    ->where('grade', 2)
                                    ->get();
                                if ($prizes->isEmpty()) {
                                    throw new \Exception('盲盒没有设置奖品');
                                }
                            }
                        }

                        // 计算总概率
                        $totalChance = $prizes->sum('chance');
                        // 生成一个介于 0 和总概率之间的随机数
                        $randomNumber = mt_rand() / mt_getrandmax() * $totalChance;
                        // 累加概率，确定中奖奖品
                        $currentChance = 0.0;
                        // 用户可能单独增加额外的概率
                        $currentChance += $order->user->chance;
//                        Log::info('第'.$i+1 .'次抽奖');
//                        Log::info('总概率：'.$totalChance);
//                        Log::info('用户额外概率：'.$order->user->chance);
//                        Log::info('随机概率：'.$randomNumber);
                        // 对奖品列表进行随机排序
                        $prizes = $prizes->shuffle();

                        foreach ($prizes as $prize) {
                            $currentChance += $prize->chance;
//                            Log::info('当前概率：'.$currentChance.'-------'.'奖品概率：'.$prize->chance.'-------'.'奖品等级：'.$prize->grade_text);
                            if ($randomNumber <= $currentChance) {
//                                Log::info('中奖了=====奖品ID：'.$prize->id);
                                $winnerPrize['list'][] = $prize;
                                if ($prize->grade == 5) {
                                    $winnerPrize['gt_n'] = 1;
                                }
                                if ($order->user->kol == 0) {
                                    //普通用户才增加奖金池
                                    // 增加奖金池金额
                                    $pool_amount = $order->pay_amount / $order->times * (1 - $order->box->rate) - $prize->price;
                                    $prize->box->increment('pool_amount', $pool_amount);
                                }else{
                                    $pool_amount = $order->pay_amount / $order->times * (1 - $order->box->kol_rate) - $prize->price;
                                    $prize->box->increment('kol_pool_amount', $pool_amount);
                                }
                                break;
                            }
                        }
                    }

                    $online = Cache::has("private-user-{$order->user_id}");
                    if (!$online) {
                        Cache::set("private-user-{$order->user_id}-winner_prize", $winnerPrize);
                    } else {
                        $api = new Api(
                            'http://127.0.0.1:3232',
                            config('plugin.webman.push.app.app_key'),
                            config('plugin.webman.push.app.app_secret')
                        );
                        // 给客户端推送私有 prize_draw 事件的消息
                        $api->trigger("private-user-{$order->user_id}", 'prize_draw', [
                            'winner_prize' => $winnerPrize
                        ]);
                        Log::info("推送消息成功:private-user-{$order->user_id}-winner_prize");
                    }


                    foreach ($winnerPrize['list'] as $item) {
                        // 发放奖品并且记录
                        if ($userPrize = UsersPrize::where(['user_id' => $order->user_id, 'box_prize_id' => $item->id, 'price' => $item->price])->first()) {
                            $userPrize->increment('num');
                        } else {
                            UsersPrize::create([
                                'user_id' => $order->user_id,
                                'box_prize_id' => $item->id,
                                'price' => $item->price,
                                'num' => 1,
                                'mark' => '抽奖获得',
                                'grade' => $item->grade,
                            ]);
                        }

                        UsersPrizeLog::create([
                            'draw_id' => $draw->id,
                            'user_id' => $order->user_id,
                            'box_prize_id' => $item->id,
                            'mark' => '抽奖获得',
                            'price' => $item->price,
                            'type' => 0,
                            'grade' => $item->grade,
                            'num' => 1,
                        ]);
                    }
                    UsersDisburse::create([
                        'user_id' => $order->user_id,
                        'amount' => $order->pay_amount,
                        'mark' => $order->box->name,
                        'type' => $paytype == 'alipay' ? 1 : ($paytype == 'balance' ? 2 : 3),
                        'scene' => 1,
                    ]);

                    break;
                case 'goods':
                    $order = GoodsOrder::where(['ordersn' => $out_trade_no, 'status' => 1])->first();
                    if (!$order) {
                        throw new \Exception('订单不存在');
                    }
                    $order->status = 2;
                    $order->pay_type = $paytype == 'alipay' ? 1 : ($paytype == 'balance' ? 2 : 3);
                    $order->pay_at = date('Y-m-d H:i:s');
                    $order->save();
                    if ($order->user->new == 1 && ($paytype == 'unipay' || $paytype == 'alipay')) {
                        $order->user->new = 0;
                        $order->user->new_time = date('Y-m-d H:i:s');
                        $order->user->save();
                    }

                    if ($userPrize = UsersPrize::where(['user_id' => $order->user_id, 'box_prize_id' => $order->goods->prize_id, 'price' => $order->goods->boxPrize->price])->first()) {
                        $userPrize->increment('num');
                    } else {
                        //给用户发放赏袋
                        UsersPrize::create([
                            'user_id' => $order->user_id,
                            'box_prize_id' => $order->goods->prize_id,
                            'price' => $order->goods->boxPrize->price,
                            'mark' => '购买商品获得',
                            'num' => 1,
                            'grade' => $order->goods->boxPrize->grade,
                        ]);
                    }


                    UsersPrizeLog::create([
                        'user_id' => $order->user_id,
                        'box_prize_id' => $order->goods->prize_id,
                        'mark' => $order->goods->boxPrize->name,
                        'type' => 5,
                        'price' => $order->goods->boxPrize->price,
                        'grade' => $order->goods->boxPrize->grade,
                        'num' => 1,
                    ]);

                    UsersDisburse::create([
                        'user_id' => $order->user_id,
                        'amount' => $order->pay_amount,
                        'mark' => $order->goods->boxPrize->name,
                        'type' => $paytype == 'alipay' ? 1 : ($paytype == 'balance' ? 2 : 3),
                        'scene' => 2,
                    ]);
                    break;
                case 'freight':
                    $order = Deliver::where(['ordersn' => $out_trade_no, 'status' => 0])->first();
                    if (!$order) {
                        throw new \Exception('订单不存在');
                    }
                    $order->pay_type = $paytype == 'alipay' ? 1 : ($paytype == 'balance' ? 2 : 3);
                    $order->status = 1;
                    $order->pay_time = date('Y-m-d H:i:s');
                    $order->save();

                    //支付成功  删除用户的奖品
                    UsersPrizeLog::create([
                        'user_id' => $order->userPrize->user_id,
                        'box_prize_id' => $order->box_prize_id,
                        'mark' => '发货成功，删除奖品',
                        'type' => 4,
                        'price' => $order->boxPrize->price,
                        'grade' => $order->boxPrize->grade,
                        'num' => $order->num,
                    ]);
                    $order->userPrize->decrement('num', $order->num);
                    if ($order->userPrize->num <= 0) {
                        $order->userPrize->delete();
                    }

                    break;
                case 'dream':
                    $order = DreamOrders::where(['ordersn' => $out_trade_no, 'status' => 1])->first();
                    if (!$order) {
                        throw new \Exception('订单不存在');
                    }
                    $order->pay_type = $paytype == 'alipay' ? 1 : ($paytype == 'balance' ? 2 : 3);
                    $order->status = 2;
                    $order->pay_at = date('Y-m-d H:i:s');
                    $order->save();
                    $probability = $order->probability;
                    $probability = $probability / 2;

                    if ($order->user->new == 1 && ($paytype == 'unipay' || $paytype == 'alipay')) {
                        $order->user->new = 0;
                        $order->user->new_time = date('Y-m-d H:i:s');
                        $order->user->save();
                    }
                    $winnerPrize = ['gt_n' => 0, 'list' => []];
                    // 生成奖品ID数组并创建订单奖品记录
                    for ($i = 0; $i < $order->times; $i++) {
                        $is_big_prize = mt_rand(1, 100) <= $probability;
                        $prize = $is_big_prize ? $order->bigPrize : $order->smallPrize;
                        $winnerPrize['list'][] = $prize;
                        if ($prize->grade == 5) {
                            $winnerPrize['gt_n'] = 1;
                        }

                    }

                    $online = Cache::has("private-user-{$order->user_id}");
                    if (!$online) {
                        Cache::set("private-user-{$order->user_id}-winner_prize", $winnerPrize);
                    } else {
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
                    }


                    $data = [];
                    $total_price = 0;
                    foreach ($winnerPrize['list'] as $item) {
                        if ($userPrize = UsersPrize::where(['user_id' => $order->user_id, 'box_prize_id' => $item->id, 'price' => $item->price])->first()) {
                            $userPrize->increment('num');
                        } else {
                            UsersPrize::create([
                                'user_id' => $order->user_id,
                                'box_prize_id' => $item->id,
                                'price' => $item->price,
                                'num' => 1,
                                'mark' => '梦想DIY获得',
                                'grade' => $item->grade,
                            ]);
                        }
                        UsersPrizeLog::create([
                            'user_id' => $order->user_id,
                            'box_prize_id' => $item->id,
                            'mark' => '梦想DIY获得',
                            'type' => 6,
                            'num' => 1,
                            'price' => $item->price,
                            'grade' => $item->grade,
                        ]);
                        $data[] = ['box_prize_id' => $item->id, 'type' => $item->id == $order->big_prize_id ? 1 : 0];
                        $total_price += $item->price;
                    }
                    $order->orderPrize()->createMany($data);
                    // 计算利润
                    $order->profit = $order->pay_amount - $total_price;
                    // 保存订单信息
                    $order->save();

                    UsersDisburse::create([
                        'user_id' => $order->user_id,
                        'amount' => $order->pay_amount,
                        'mark' => '梦想DIY抽奖',
                        'type' => $paytype == 'alipay' ? 1 : ($paytype == 'balance' ? 2 : 3),
                        'scene' => 3,
                    ]);
                    break;
                default:
                    throw new \Exception('回调错误');
            }

        } catch (\Throwable $e) {
            Log::error('支付回调错误');
            Log::error(json_encode($request->all()));
            Log::error($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

}
