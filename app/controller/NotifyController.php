<?php

namespace app\controller;

use DateTime;
use DateTimeZone;
use EasyWeChat\MiniApp\Application;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * @throws \Exception
     */
    private function pay(Request $request)
    {
        try {
            $paytype = $request->input('paytype');
            $pay = Pay::wechat(config('payment'));

            if ($paytype == 'wechat') {
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
            } else {
                throw new \Exception('支付类型错误');
            }

            switch ($attach) {
                case 'box':
                    $order = BoxOrder::where(['ordersn' => $out_trade_no, 'status' => 1])->first();
                    if (!$order) {
                        throw new \Exception('订单不存在');
                    }
                    $order->status = 2;
                    $order->pay_at = date('Y-m-d H:i:s');
                    $order->save();
                    if ($order->userCoupon) {
                        $order->userCoupon->status = 2;
                        $order->userCoupon->save();
                    }
                    if ($order->user->new == 1 && $paytype == 'wechat') {
                        $order->user->new = 0;
                        $order->user->new_time = date('Y-m-d H:i:s');
                        $order->user->save();
                    }
                    //开始执行抽奖操作
                    $draw = UsersDrawLog::create([
                        'user_id' => $order->user_id,
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
                            ->when(!empty($order->level_id), function (Builder $query) use ($order) {
                                $query->where('level_id', $order->level_id);
                            })
                            ->get();
                        // 如果没有可用奖品，返回提示
                        if ($prizes->isEmpty()) {
                            BoxPrize::where(['box_id' => $order->box_id])
                                ->when(!empty($order->level_id), function (Builder $query) use ($order) {
                                    $query->where('level_id', $order->level_id);
                                })
                                ->update(['num' => DB::raw('total')]);

                            $prizes = BoxPrize::where(['box_id' => $order->box_id])
                                ->when(!empty($order->level_id), function (Builder $query) use ($order) {
                                    $query->where('level_id', $order->level_id);
                                })
                                ->get(); // 重新获取奖品列表
                            if ($prizes->isEmpty()) {
                                throw new \Exception('没有设置奖池');
                            }
                        }
                        // 计算总概率
                        $totalChance = $prizes->sum('chance');
                        // 生成一个介于 0 和总概率之间的随机数
                        $randomNumber = mt_rand() / mt_getrandmax() * $totalChance;

                        // 累加概率，确定中奖奖品
                        $currentChance = 0.0;
                        //达人拥有额外的中奖率
                        if ($order->user->kol == 0) {
                            $currentChance += $order->user->chance;
                        }

                        foreach ($prizes as $prize) {
                            $currentChance += $prize->chance;

                            if ($randomNumber < $currentChance) {
                                //达人抽奖不减数量
                                if ($order->user->kol == 0) {
                                    $prize->decrement('num');
                                }
                                $winnerPrize[] = $prize;
                                // 发放奖品并且记录
                                break;
                            }
                        }
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
                    foreach ($winnerPrize as $item){
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
                        ]);
                    }

                    UsersDisburse::create([
                        'user_id' => $order->user_id,
                        'amount' => $order->pay_amount,
                        'mark' => '购买盲盒',
                        'type' => $paytype == 'wechat' ? 1 : 2,
                    ]);

                    if ($paytype == 'wechat') {
                        $app = new Application(config('wechat'));
                        $api = $app->getClient();
                        $date = new DateTime(date('Y-m-d H:i:s'), new DateTimeZone('Asia/Shanghai'));
                        $formatted_date = $date->format('c');
                        $api->postJson('/wxa/sec/order/upload_shipping_info', [
                            'order_key' => ['order_number_type' => 1, 'mchid' => $mchid, 'out_trade_no' => $out_trade_no],
                            'logistics_type' => 3,
                            'delivery_mode' => 1,
                            'shipping_list' => [[
                                'item_desc' => $order->box->name,
                            ]],
                            'upload_time' => $formatted_date,
                            'payer' => ['openid' => $openid]
                        ]);
                    }

                    break;
                case 'goods':
                    $order = GoodsOrder::where(['ordersn' => $out_trade_no, 'status' => 1])->first();
                    if (!$order) {
                        throw new \Exception('订单不存在');
                    }
                    $order->status = 2;
                    $order->pay_at = date('Y-m-d H:i:s');
                    $order->save();
                    if ($order->user->new == 1 && $paytype == 'wechat') {
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
                        ]);
                    }


                    UsersPrizeLog::create([
                        'user_id' => $order->user_id,
                        'box_prize_id' => $order->goods->prize_id,
                        'mark' => $order->goods->boxPrize->name,
                        'type' => 5,
                        'price' => $order->goods->boxPrize->price,
                        'grade' => $order->goods->boxPrize->grade,
                    ]);

                    UsersDisburse::create([
                        'user_id' => $order->user_id,
                        'amount' => $order->pay_amount,
                        'mark' => $order->goods->boxPrize->name,
                        'type' => $paytype == 'wechat' ? 1 : 2,
                    ]);
                    if ($paytype == 'wechat') {
                        $app = new Application(config('wechat'));
                        $api = $app->getClient();
                        $date = new DateTime(date('Y-m-d H:i:s'), new DateTimeZone('Asia/Shanghai'));
                        $formatted_date = $date->format('c');
                        $api->postJson('/wxa/sec/order/upload_shipping_info', [
                            'order_key' => ['order_number_type' => 1, 'mchid' => $mchid, 'out_trade_no' => $out_trade_no],
                            'logistics_type' => 3,
                            'delivery_mode' => 1,
                            'shipping_list' => [[
                                'item_desc' => $order->goods->boxPrize->name,
                            ]],
                            'upload_time' => $formatted_date,
                            'payer' => ['openid' => $openid]
                        ]);
                    }
                    break;
                case 'freight':
                    $order = Deliver::where(['ordersn' => $out_trade_no, 'status' => 0])->first();
                    if (!$order) {
                        throw new \Exception('订单不存在');
                    }
                    $order->status = 1;
                    $order->pay_time = date('Y-m-d H:i:s');
                    $order->save();

                    $order->detail->each(function (DeliverDetail $item) {
                        //支付成功  删除用户的奖品
                        UsersPrizeLog::create([
                            'user_id' => $item->userPrize->user_id,
                            'box_prize_id' => $item->box_prize_id,
                            'mark' => '发货成功，删除奖品',
                            'type' => 4,
                            'price' => $item->boxPrize->price,
                            'grade' => $item->boxPrize->grade,
                        ]);
                        $item->userPrize->decrement('num',$item->num);
                        if ($item->userPrize->num <= 0) {
                            $item->userPrize->delete();
                        }
                    });

                    if ($paytype == 'wechat') {
                        $app = new Application(config('wechat'));
                        $api = $app->getClient();
                        $date = new DateTime(date('Y-m-d H:i:s'), new DateTimeZone('Asia/Shanghai'));
                        $formatted_date = $date->format('c');
                        $api->postJson('/wxa/sec/order/upload_shipping_info', [
                            'order_key' => ['order_number_type' => 1, 'mchid' => $mchid, 'out_trade_no' => $out_trade_no],
                            'logistics_type' => 3,
                            'delivery_mode' => 1,
                            'shipping_list' => [[
                                'item_desc' => '发货运费'
                            ]],
                            'upload_time' => $formatted_date,
                            'payer' => ['openid' => $openid]
                        ]);
                    }
                    break;
                case 'dream':
                    $order = DreamOrders::where(['ordersn' => $out_trade_no, 'status' => 1])->first();
                    if (!$order) {
                        throw new \Exception('订单不存在');
                    }
                    $order->status = 2;
                    $order->pay_at = date('Y-m-d H:i:s');

                    $probability = $order->probability;
                    $probability = $probability / 2;
                    $big_prize_id = $order->big_prize_id;
                    $small_prize_id = $order->small_prize_id;
                    if ($order->user->new == 1 && $paytype == 'wechat') {
                        $order->user->new = 0;
                        $order->user->new_time = date('Y-m-d H:i:s');
                        $order->user->save();
                    }

                    // 生成奖品ID数组并创建订单奖品记录
                    $prize_ids = collect();
                    for ($i = 0; $i < $order->times; $i++) {
                        $is_big_prize = mt_rand(1, 100) <= $probability;
                        $prize_id = $is_big_prize ? $big_prize_id : $small_prize_id;
                        $prize_ids->push($prize_id);

                        $order->orderPrize()->create(['box_prize_id' => $prize_id]);

                        $prize = BoxPrize::find($prize_id);
                        if ($userPrize = UsersPrize::where(['user_id' => $order->user_id, 'box_prize_id' => $prize->id, 'price' => $prize->price])->first()) {
                            $userPrize->increment('num');
                        } else {
                            UsersPrize::create([
                                'user_id' => $order->user_id,
                                'box_prize_id' => $prize_id,
                                'price' => $prize->price,
                                'num' => 1,
                                'mark' => '梦想DIY获得',
                            ]);
                        }


                        UsersPrizeLog::create([
                            'user_id' => $order->user_id,
                            'box_prize_id' => $prize_id,
                            'mark' => '梦想DIY获得',
                            'type' => 6
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
                    UsersDisburse::create([
                        'user_id' => $order->user_id,
                        'amount' => $order->pay_amount,
                        'mark' => '梦想DIY抽奖',
                        'type' => $paytype == 'wechat' ? 1 : 2,
                    ]);

                    if ($paytype == 'wechat') {
                        $app = new Application(config('wechat'));
                        $api = $app->getClient();
                        $date = new DateTime(date('Y-m-d H:i:s'), new DateTimeZone('Asia/Shanghai'));
                        $formatted_date = $date->format('c');
                        $api->postJson('/wxa/sec/order/upload_shipping_info', [
                            'order_key' => ['order_number_type' => 1, 'mchid' => $mchid, 'out_trade_no' => $out_trade_no],
                            'logistics_type' => 3,
                            'delivery_mode' => 1,
                            'shipping_list' => [[
                                'item_desc' => '梦想DIY抽奖'
                            ]],
                            'upload_time' => $formatted_date,
                            'payer' => ['openid' => $openid]
                        ]);
                    }
                    break;
                default:
                    throw new \Exception('回调错误');
            }

        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
    }

}
