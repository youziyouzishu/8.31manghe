<?php

namespace app\controller;

use app\service\Pay;
use app\tool\Random;
use Illuminate\Database\Query\Builder;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\Dream;
use plugin\admin\app\model\DreamOrders;
use plugin\admin\app\model\DreamOrdersPrize;
use plugin\admin\app\model\User;
use support\Db;
use support\Request;
use Tinywan\Jwt\JwtToken;

class DreamController extends BaseController
{
    function index(Request $request)
    {
        $type = $request->post('type');
        if (!in_array($type, [1, 2])) {
            return $this->fail('参数错误');
        }
        // 加载 dreams 并按 boxPrize 的 price 排序
        $dreams = Dream::with(['boxPrize' => function (Builder $query) use ($type) {
            $query->when($type == 1, function (Builder $query) {
                $query->orderByDesc('price');
            }, function (Builder $query) {
                $query->orderBy('price');
            });
        }])->where('type', $type)->get();

        // 提取 boxPrize 数据
        $boxPrizes = $dreams->flatMap(function (Dream $dream) {
            return $dream->boxPrize;
        });
        return $this->success('成功', $boxPrizes);
    }

    function getPrice(Request $request)
    {
        $big_prize_id = $request->post('big_prize_id');
        $small_prize_id = $request->post('small_prize_id');
        $probability = $request->post('probability');//高价值中奖概率

        $big_prize = BoxPrize::find($big_prize_id);
        $small_prize = BoxPrize::find($small_prize_id);

        if (!$big_prize || !$small_prize) {
            return $this->fail('大奖或小奖不存在');
        }
        if ($big_prize->price <= $small_prize->price) {
            return $this->fail('大奖必须大于小奖');
        }

        $big_prize_price = $big_prize->price;
        $small_prize_price = $small_prize->price;


        $r = 0.27; //期望利润率
        $small_probability = 1 - $probability / 100;//低价值中奖概率

        $price = ($big_prize_price * $probability + $small_prize_price * $small_probability) * (1 + $r); //单抽价格

        $data = [
            'one_times_price' => number_format($price, 2),
            'three_times_price' => number_format($price * 3, 2),
            'ten_times_price' => number_format($price * 10, 2),
        ];
        return $this->success('成功', $data);
    }

    function draw(Request $request)
    {
        $times = $request->post('times');
        $big_prize_id = $request->post('big_prize_id');
        $small_prize_id = $request->post('small_prize_id');
        $probability = $request->post('probability');//高价值中奖概率
        // 启动事务
        Db::beginTransaction();
        try {
            $big_prize = BoxPrize::find($big_prize_id);
            $small_prize = BoxPrize::find($small_prize_id);

            if (!$big_prize || !$small_prize) {
                return $this->fail('大奖或小奖不存在');
            }
            if ($big_prize->price <= $small_prize->price) {
                return $this->fail('大奖必须大于小奖');
            }

            $big_prize_price = $big_prize->price;
            $small_prize_price = $small_prize->price;

            $r = 0.27; //期望利润率
            $small_probability = 1 - $probability;//低价值中奖概率

            $price = ($big_prize_price * $probability + $small_prize_price * $small_probability) * (1 + $r); //单抽价格

            $pay_amount = number_format($price * $times, 2);
            $user = User::find($request->uid);
            $ordersn = Util::ordersn();
            $orders = DreamOrders::create([
                'user_id' => $request->uid,
                'pay_amount' => $pay_amount,
                'ordersn' => $ordersn,
                'times' => $times,
                'big_prize_id' => $big_prize_id,
                'small_prize_id' => $small_prize_id,
                'probability' => $probability,
            ]);

            if ($user->money >= $pay_amount) {
                $orders->pay_type = 2;
                $orders->save();
                //水晶支付
                $ret = [];
                User::money(-$pay_amount, $request->uid, '梦想DIY抽奖');
                $code = 3;
                $msg = '支付成功';

                // 创建一个新的请求对象 直接调用支付
                $notify = new NotifyController();
                $request->set('get',['paytype' => 'balance', 'out_trade_no' => $ordersn, 'attach' => 'dream']);
                $res = $notify->pay($request);
                $res = json_decode($res->rawBody());
                if ($res->code == 1) {
                    //支付失败
                    // 回滚事务
                    Db::rollBack();
                    return $this->fail($res->msg);
                }
            } else {
                $orders->pay_type = 1;
                $orders->save();
                $ret = Pay::pay($pay_amount, $ordersn, '梦想DIY抽奖', 'dream', JwtToken::getUser()->openid);
                $code = 4;
                $msg = '开始微信支付';
            }
            Db::commit();
            return $this->json($code, $msg, $ret);
        } catch (\Throwable $e) {
            Db::rollBack();
            return $this->fail($e->getMessage());
        }
    }

    function getOrders(Request $request)
    {
        $rows = DreamOrdersPrize::with(['boxPrize', 'orders.user'])
            ->orderByDesc('id')
            ->paginate()
            ->items();
        return $this->success('成功', $rows);
    }

    function getUserOrders(Request $request)
    {
        $rows = DreamOrdersPrize::whereHas('orders', function ($query) use ($request) {
            $query->where('user_id', $request->uid);
        })
            ->with(['boxPrize', 'orders.user'])
            ->orderByDesc('id')
            ->paginate()
            ->items();
        return $this->success('成功', $rows);
    }

}
