<?php

namespace app\controller;

use app\service\Pay;
use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\Goods;
use plugin\admin\app\model\GoodsClass;
use plugin\admin\app\model\GoodsOrder;
use plugin\admin\app\model\Option;
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
        $rows = Goods::with(['boxPrize'])->when(!empty($class_id), function (Builder $query) use ($class_id) {
                return $query->where('class_id', $class_id);
            })
            ->paginate()
            ->items();

        return $this->success('成功', $rows);
    }

    function detail(Request $request)
    {
        $goods_id = $request->post('goods_id');
        $row = Goods::with(['boxPrize'])->find($goods_id);
        if (!$row){
            return $this->fail('商品不存在');
        }
        $name = 'system_config';
        $config = Option::where('name', $name)->value('value');
        $config = json_decode($config);
        $row->pre_sale = $config->logo->pre_sale;

        return $this->success('成功', $row);
    }

    function pay(Request $request)
    {
        $goods_id = $request->post('goods_id');
        $goods = Goods::find($goods_id);
        if (!$goods) {
            return $this->fail('商品不存在');
        }
        $user = User::find($request->uid);
        $ordersn = Util::ordersn();
        $amount = $goods->boxPrize->price;

        $pay_amount = $amount;

        Db::beginTransaction();
        try {

            $goodsData = [
                'user_id' => $request->uid,
                'goods_id' => $goods->id,
                'amount' => $amount,
                'pay_amount' => 0,
                'ordersn' => $ordersn,
            ];
            $order = GoodsOrder::create($goodsData);
            if ($user->money >= $pay_amount) {
                if ($pay_amount <= 0) {
                    $pay_amount = 0.01;
                }
                $order->pay_amount = $pay_amount;
                $order->pay_type = 2;
                $order->save();
                $ret = [];
                //余额支付
                User::money(-$pay_amount, $request->uid, '购买商品-'.$goods->boxPrize->name);
                $code = 3;
                // 创建一个新的请求对象 直接调用支付
                $notify = new NotifyController();
                $request->set('get',['out_trade_no' => $ordersn, 'attach' => 'goods']);
                $res = $notify->balance($request);
                $res = json_decode($res->rawBody());
                if ($res->code == 1) {
                    //支付失败
                    // 回滚事务
                    Db::rollBack();
                    return $this->fail($res->msg);
                }

            } else {
                // 生成 1 到 9 之间的随机整数
                $randomCents = rand(1, 9);
                // 将随机整数转换为小数（0.01 到 0.09）
                $randomDecimal = $randomCents / 100;
                // 从原价中减去随机小数
                $pay_amount = function_exists('bcsub') ? bcsub($amount, $randomDecimal, 2) : $amount - $randomDecimal;
                if ($pay_amount <= 0) {
                    $pay_amount = 0.01;
                }
                $order->pay_amount = $pay_amount;
                $order->save();
                $ret = ['scene'=>'goods','ordersn'=>$ordersn];
                $code = 4;
            }

            Db::commit();
            return $this->success('成功',[
                'code'=>$code,
                'ret'=>$ret
            ]);
        } catch (\Throwable $e) {
            // 回滚事务
            Db::rollBack();
            return $this->fail($e->getMessage());
        }


    }

}
