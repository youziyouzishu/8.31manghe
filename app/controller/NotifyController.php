<?php

namespace app\controller;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Lottery;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\DreamOrders;
use plugin\admin\app\model\GoodsOrder;
use plugin\admin\app\model\UsersDisburse;
use plugin\admin\app\model\UsersDrawLog;
use plugin\admin\app\model\UsersGaine;
use plugin\admin\app\model\UsersGaineLog;
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
                    } else {
                        $order->box->increment('kol_consume_amount', $order->pay_amount);
                    }
                    //开始执行抽奖操作
                    $draw = UsersDrawLog::create([
                        'user_id' => $order->user_id,
                        'times' => $order->times,
                        'box_id' => $order->box_id,
                        'level_id' => $order->level_id,
                        'ordersn' => $out_trade_no,
                        'chest_id' => $order->chest_id,
                    ]); #创建抽奖记录
                    $winnerPrize = ['gt_n' => 0, 'list' => []];
                    if ($order->box->type == 4) {
                        //闯关赏抽奖
                        for ($i = 1; $i <= $order->times; $i++) {
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
                                    } else {
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
                                        $order->box->increment('pool_amount', $pool_amount);
                                    } else {
                                        $pool_amount = $order->pay_amount / $order->times * (1 - $order->box->kol_rate) - $prize->price;
                                        $order->box->increment('kol_pool_amount', $pool_amount);
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


                    } elseif ($order->box->type == 5) {
                        //一番赏抽奖
                        //抽中N赏
                        $item = BoxPrize::where(['box_id' => $order->box_id])->where('grade', 2)->first();
                        $winnerPrize['list'][] = $item;
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

                        // 发放奖品并且记录
                        if ($userPrize = UsersPrize::where(['user_id' => $order->user_id, 'box_prize_id' => $item->id, 'price' => $item->price])->first()) {
                            $userPrize->increment('num');
                        } else {
                            UsersPrize::create([
                                'user_id' => $order->user_id,
                                'box_prize_id' => $item->id,
                                'price' => $item->price,
                                'num' => 1,
                                'mark' => '一番赏抽奖获得',
                                'grade' => $item->grade,
                            ]);
                        }
                        UsersPrizeLog::create([
                            'draw_id' => $draw->id,
                            'user_id' => $order->user_id,
                            'box_prize_id' => $item->id,
                            'mark' => '一番赏抽奖获得',
                            'price' => $item->price,
                            'type' => 0,
                            'grade' => $item->grade,
                            'num' => 1,
                        ]);

                        $total = BoxPrize::where(['box_id' => $order->box_id])->where('grade', 2)->sum('num');
                        $num = $order->chest->orders()->where('status', 2)->count();
                        if ($total - $num == 0) {
                            //随机分配SSS
                            $prizes = $order->box->boxPrize()->where('grade','<>',2)->get();
                            $draws = UsersDrawLog::where('chest_id', $order->chest_id)->get();
                            foreach ($prizes as $prize) {
                                for ($i = 1; $i <= $prize->num; $i++) {
                                    // 随机选择一个用户ID
                                    $draw = $draws->random();
                                    // 将奖品分配给随机选择的用户
                                    if ($userPrize = UsersPrize::where(['user_id' => $draw->user_id, 'box_prize_id' => $prize->id, 'price' => $prize->price])->first()) {
                                        $userPrize->increment('num');
                                    } else {
                                        UsersPrize::create([
                                            'user_id' => $draw->user_id,
                                            'box_prize_id' => $prize->id,
                                            'price' => $prize->price,
                                            'num' => 1,
                                            'mark' => '一番赏分配获得',
                                            'grade' => $prize->grade,
                                        ]);
                                    }
                                    UsersPrizeLog::create([
                                        'draw_id' => $draw->id,
                                        'user_id' => $draw->user_id,
                                        'box_prize_id' => $prize->id,
                                        'mark' => '一番赏分配获得',
                                        'price' => $prize->price,
                                        'type' => 0,
                                        'grade' => $prize->grade,
                                        'num' => 1,
                                    ]);
                                }
                            }
                        }
                    } elseif ($order->box->type == 6) {
                        $gaine = $order->box->gaine()->inRandomOrder()->get();
                        $prize = $order->box->boxPrize()->whereNull('gaine_id')->inRandomOrder()->get();

                        // 1️⃣ 初始化最终奖池
                        $finalPool = [];

                        // 2️⃣ 处理 gaine 数据：获取每个抽奖活动下的奖品
                        foreach ($gaine as $item) {
                            $finalPool[] = [
                                'type' => 'gaine',
                                'id' => $item->id,
                                'name' => $item->name,
                                'chance' => $item->chance,
                                'image' => $item->image,
                                'grade' => 0,
                                'price' => 0,
                                'show_price' => 0,
                            ];
                        }

                        // 3️⃣ 处理普通 prize 数据
                        foreach ($prize as $normalPrize) {
                            $finalPool[] = [
                                'type' => 'normal',
                                'id' => $normalPrize->id,
                                'name' => $normalPrize->name,
                                'chance' => $normalPrize->chance,
                                'image' => $normalPrize->image,
                                'grade' => $normalPrize->grade,
                                'price' => $normalPrize->price,
                                'show_price' => $normalPrize->show_price,
                            ];
                        }

                        // 4️⃣ 如果奖池为空，抛出异常
                        if (empty($finalPool)) {
                            throw new \Exception('没有可抽奖品');
                        }

                        // 5️⃣ 计算总概率
                        $totalChance = array_sum(array_column($finalPool, 'chance'));


                        for ($i = 1; $i <= $order->times; $i++) {
                            // 6️⃣ 生成随机数
                            $randomNumber = mt_rand() / mt_getrandmax() * $totalChance;
                            // 7️⃣ 执行抽奖逻辑
                            $currentChance = 0;
                            $currentChance += $order->user->chance;
                            foreach ($finalPool as $item) {
                                $currentChance += $item['chance'];
                                if ($randomNumber <= $currentChance) {
                                    $winnerPrize['list'][] = $item;
                                    if ($order->user->kol == 0) {
                                        //普通用户才增加奖金池
                                        // 增加奖金池金额
                                        $pool_amount = $order->pay_amount / $order->times * (1 - $order->box->rate) - $item['price'];
                                        $order->box->increment('pool_amount', $pool_amount);
                                    } else {
                                        $pool_amount = $order->pay_amount / $order->times * (1 - $order->box->kol_rate) - $item['price'];
                                        $order->box->increment('kol_pool_amount', $pool_amount);
                                    }
                                    break;
                                }
                            }
                        }


                        $online = Cache::has("private-user-{$order->user_id}");
                        dump('在线状态：'.$online);
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
                            dump("推送消息成功:private-user-{$order->user_id}-winner_prize");
                            Log::info("推送消息成功:private-user-{$order->user_id}-winner_prize");
                        }
                        dump('中奖数量：'.count($winnerPrize['list']));

                        foreach ($winnerPrize['list'] as $item) {

                            if ($item['type'] == 'normal') {
                                // 发放奖品并且记录
                                if ($userPrize = UsersPrize::where(['user_id' => $order->user_id, 'box_prize_id' => $item['id'], 'price' => $item['price']])->first()) {
                                    $userPrize->increment('num');
                                } else {
                                    UsersPrize::create([
                                        'user_id' => $order->user_id,
                                        'box_prize_id' => $item['id'],
                                        'price' => $item['price'],
                                        'num' => 1,
                                        'mark' => '开箱赏抽奖获得',
                                        'grade' => $item['grade'],
                                    ]);
                                }

                                UsersPrizeLog::create([
                                    'draw_id' => $draw->id,
                                    'user_id' => $order->user_id,
                                    'box_prize_id' => $item['id'],
                                    'mark' => '开箱赏抽奖获得',
                                    'price' => $item['price'],
                                    'type' => 0,
                                    'grade' => $item['grade'],
                                    'num' => 1,
                                ]);
                            } else {
                                UsersGaine::create([
                                    'user_id' => $order->user_id,
                                    'gaine_id' => $item['id'],
                                    'draw_id' => $draw->id,
                                ]);
                            }

                        }
                        UsersDisburse::create([
                            'user_id' => $order->user_id,
                            'amount' => $order->pay_amount,
                            'mark' => $order->box->name,
                            'type' => $paytype == 'alipay' ? 1 : ($paytype == 'balance' ? 2 : 3),
                            'scene' => 1,
                        ]);
                    } else {
                        //普通盲盒抽奖
                        for ($i = 1; $i <= $order->times; $i++) {
                            Log::info('第' . $i . '抽');
                            $grades_need_num = [];
                            #这个盲盒中的等级
                            $grades = $order->box->boxPrize()->where('grade', '>', 2)->distinct()->pluck('grade')->toArray();
                            foreach ($grades as $grade) {
                                $total_chance = $order->box->boxPrize()->where('grade', $grade)->sum('chance');
                                $need_num_1 = round(100 / $total_chance);
                                $need_num_2 = round($need_num_1 * (1 + $order->box->inc_rate));
                                $need_num = mt_rand($need_num_1, $need_num_2);
                                $grades_need_num[$grade] = $need_num;
                            }

                            #增加盲盒所有等级抽奖次数
                            $order->box->grade()->whereIn('grade', $grades)->where(function ($query) use ($order) {
                                if ($order->user->kol == 1) {
                                    $query->where('type', 2);
                                } else {
                                    $query->where('type', 1);
                                }
                            })->increment('num', 1);
                            //每次循环都刷新盲盒
                            $order->refresh();
                            $box_grades = $order->box->grade()->whereIn('grade', $grades)->where(function ($query) use ($order) {
                                if ($order->user->kol == 1) {
                                    $query->where('type', 2);
                                } else {
                                    $query->where('type', 1);
                                }
                            })->orderByDesc('grade')->get();


                            $selected_grade = null;
                            foreach ($box_grades as $box_grade) {
                                Log::info('当前等级' . BoxPrize::getGradeList()[$box_grade->grade] . '   抽奖次数' . $box_grade->num . '   出奖需要的次数：' . $grades_need_num[$box_grade->grade] . '   奖金池：' . ($order->user->kol == 1 ? $order->box->kol_pool_amount : $order->box->pool_amount) . '   最低奖品价值：' . $order->box->boxPrize()->where('grade', $box_grade->grade)->min('price'));

                                if ($box_grade->num >= $grades_need_num[$box_grade->grade] && $order->box->boxPrize()->where('grade', $box_grade->grade)->where(function ($query) use ($order) {
                                        if ($order->user->kol == 1) {
                                            $query->whereBetween('price', [0, $order->box->kol_pool_amount]);
                                        } else {
                                            $query->whereBetween('price', [0, $order->box->pool_amount]);
                                        }
                                    })->exists()) {
                                    Log::info('开始判定');
                                    $odds = intval($box_grade->num / $grades_need_num[$box_grade->grade]);
                                    $out_of = 120;
                                    Log::info('判定概率:' . $odds . '/' . $out_of);
                                    Lottery::odds($odds, $out_of)
                                        ->winner(function () use ($box_grade, $grades_need_num, &$selected_grade) {
                                            $box_grade->num -= $grades_need_num[$box_grade->grade];
                                            $box_grade->save();
                                            $selected_grade = $box_grade->grade;
                                            Log::info('出奖---------------------------------------------------------------');
                                        })
                                        ->loser(function () {
                                            Log::info('未抽中');
                                        })
                                        ->choose();

                                    break;
                                }
                            }
                            if ($selected_grade === null) {
                                // 默认选择 grade 为 2 的等级
                                $selected_grade = 2;
                            }
                            $prizes = $order->box->boxPrize()->where('grade', $selected_grade)->get();
                            // 计算总概率
                            $totalChance = $prizes->sum('chance');

                            // 生成一个介于 0 和总概率之间的随机数
                            $randomNumber = mt_rand() / mt_getrandmax() * $totalChance;

                            $currentChance = 0.0;
                            $prizes = $prizes->shuffle();
                            $currentChance += $order->user->chance;
                            foreach ($prizes as $prize) {
                                $currentChance += $prize->chance;
                                if ($randomNumber <= $currentChance) {
                                    $winnerPrize['list'][] = $prize;
                                    if ($prize->grade == 5) {
                                        $winnerPrize['gt_n'] = 1;
                                    }
                                    if ($order->user->kol == 0) {
                                        //普通用户才增加奖金池
                                        // 增加奖金池金额
                                        $pool_amount = $order->pay_amount / $order->times * (1 - $order->box->rate) - $prize->price;
                                        $order->box->increment('pool_amount', $pool_amount);
                                    } else {
                                        $pool_amount = $order->pay_amount / $order->times * (1 - $order->box->kol_rate) - $prize->price;
                                        $order->box->increment('kol_pool_amount', $pool_amount);
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
                    }


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
                        $data[] = ['box_prize_id' => $item->id, 'type' => $item->id == $order->big_prize_id ? 1 : 0, 'price' => $item->price, 'grade' => $item->grade];
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
            Log::error($e->getLine());
            throw new \Exception($e->getMessage());
        }
    }

}
