<?php

namespace app\controller;

use app\service\Coupon;
use app\service\Pay;
use app\tool\Random;
use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\model\Goods;
use plugin\admin\app\model\GoodsClass;
use plugin\admin\app\model\GoodsOrder;
use plugin\admin\app\model\User;
use support\Db;
use support\Request;
use Tinywan\Jwt\JwtToken;

class GoodsController extends BaseController
{
    protected array $noNeedLogin = ['class'];

    function class()
    {
        $row = GoodsClass::get();
        return $this->success('成功', $row);
    }

    function index(Request $request)
    {
        $class_id = $request->post('class_id');
        $rows = Goods::when(!empty($class_id), function (Builder $query) use ($class_id) {
                return $query->where('class_id', $class_id);
            })
            ->paginate()
            ->getCollection()
            ->map(function (Goods $item) {
                return $item->boxPrize;
            });
        return $this->success('成功', $rows);
    }

    function detail(Request $request)
    {
        $goods_id = $request->post('goods_id');
        $row = Goods::with(['boxPrize'])->find($goods_id);

        // 只返回 boxPrize 关联数据
        $boxPrize = optional($row)->boxPrize ?? [];

        return $this->success('成功', $boxPrize);
    }

    function pay(Request $request)
    {
        $goods_id = $request->post('goods_id');
        $goods = Goods::find($goods_id);
        if (!$goods) {
            return $this->fail('商品不存在');
        }
        $user = User::find($request->uid);
        $ordersn = Random::ordersn();
        $amount = $goods->prize->price;


        // 生成 1 到 9 之间的随机整数
        $randomCents = rand(1, 9);
        // 将随机整数转换为小数（0.01 到 0.09）
        $randomDecimal = $randomCents / 100;
        // 从原价中减去随机小数
        $pay_amount = function_exists('bcsub') ? bcsub($amount, $randomDecimal, 2) : $amount - $randomDecimal;
        if ($pay_amount <= 0) {
            $pay_amount = 0.01;
        }
        Db::beginTransaction();
        try {
            GoodsOrder::create([
                'user_id' => $request->uid,
                'goods_id' => $goods->id,
                'amount' => $amount,
                'pay_amount' => $pay_amount,
                'ordersn' => $ordersn,
            ]);
            if ($user->money >= $pay_amount) {
                $ret = [];
                //余额支付
                User::money(-$pay_amount, $request->uid, '购买商品');
                $code = 3;
                $msg = '支付成功';

                // 创建一个新的请求对象 直接调用支付
                $notify = new NotifyController();
                $request->set([
                    '_data' => [
                        'get' => ['paytype' => 'balance', 'out_trade_no' => $ordersn, 'attach' => 'goods']
                    ]
                ]);
                $res = $notify->pay($request);
                $res = json_decode($res);
                if ($res->code == 1) {
                    //支付失败
                    // 回滚事务
                    Db::rollBack();
                    return $this->fail($res->msg);
                }
            } else {
                //微信支付
                $ret = Pay::pay($pay_amount, $ordersn, '购买商品', 'goods', JwtToken::getUser()->openid);
                $code = 4;
                $msg = '开始微信支付';
            }
            Db::commit();
            return $this->json($code, $msg, $ret);
        } catch (\Throwable $e) {
            // 回滚事务
            Db::rollBack();
            return $this->fail($e->getMessage());
        }


    }

}
