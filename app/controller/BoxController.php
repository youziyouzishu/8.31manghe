<?php

namespace app\controller;

use app\service\Coupon;
use app\service\Pay;
use app\tool\Random;
use plugin\admin\app\model\Box;
use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\model\UsersCheckpoint;
use plugin\admin\app\model\UsersCoupon;


use support\Db;


use support\Request;
use Tinywan\Jwt\JwtToken;

class BoxController extends BaseController
{

    protected array $noNeedLogin = ['index', 'prize'];


    public function index(Request $request)
    {
        $type = $request->get('type', 1);
        $sort = $request->get('sort', 'asc');
        $rows = Box::where(['type' => $type])
            ->orderBy('id', $sort)
            ->paginate()
            ->items();
        return $this->success('成功', $rows);
    }

    function prize(Request $request)
    {
        $id = $request->get('id');
        $box = Box::find($id);
        if (empty($box)) {
            return $this->fail('盲盒不存在');
        }
        $level = $box
            ->prize()
            ->orderByDesc('level')
            ->distinct()
            ->pluck('level')
            ->values();
        $data = [];
        $data['box'] = $box;
        foreach ($level as $le) {
            $row = BoxPrize::where(['box_id' => $id, 'level' => $le])->get();
            $data['prize'][] = [
                'name' => (new BoxPrize())->getLevelList()[$le],
                'chance' => $row->sum('chance'),
                'row' => $row,
            ];
        }
        return $this->success('成功', $data);
    }


    /**  todo 重新修改数据库 每个关卡有对应图片 */
    function checkpoint(Request $request)
    {
        $id = $request->get('id');
        $box = Box::find($id);
        if (empty($box)) {
            return $this->fail('盲盒不存在');
        }

        if ($box->type != 4) {
            return $this->fail('不属于闯关盲盒');
        }

        $checkpoint = $box->prize()->where([['num','>',0]])
            ->orderBy('checkpoint')
            ->distinct()
            ->pluck('checkpoint')
            ->values();

        $data = [];
        foreach ($checkpoint as $cp){
            if (UsersCheckpoint::where(['user_id'=>$request->uid, 'box_id' => $id,'checkpoint'=>$cp])->exists()){

                $data['checkpoint'][] = [

                ];
            }else{

            }
        }


    }

    #满足条件优惠券
    function canusecoupon(Request $request)
    {
        $id = $request->get('id');
        $num = $request->get('num');
        $box = Box::find($id);
        $pay_amount = $box->price * $num; #需要支付金额

        $rows = UsersCoupon::where(['user_id' => $request->uid, 'status' => 1])
            ->get()
            ->reject(function (UsersCoupon $item) use ($pay_amount) {
                if ($item->coupon->type == 2 && $item->coupon->with_amount > $pay_amount) {
                    return true;
                }
                return false;
            })->values();

        return $this->success('成功', $rows);

    }

    function get_price(Request $request)
    {
        $id = $request->get('id');
        $num = $request->get('num');
        $coupon_id = $request->get('coupon_id');
        $row = Box::find($id);
        $amount = $row->price * $num;
        $coupon_amount = Coupon::getCouponAmount($amount, $coupon_id);

        $pay_amount = function_exists('bcsub') ? bcsub($amount, $coupon_amount, 2) : $amount - $coupon_amount;

        if ($pay_amount <= 0) {
            $pay_amount = 0.01;
        }
        return $this->success('成功', ['pay_amount' => $pay_amount]);
    }

    function pay(Request $request)
    {
        $id = $request->post('id');
        $times = $request->post('times');
        $coupon_id = $request->post('coupon_id', 0);
        $box = Box::find($id);
        if (empty($box)) {
            return $this->fail('盲盒不存在');
        }
        $amount = $box->price * $times;

        $coupon_amount = Coupon::getCouponAmount($amount, $coupon_id);

        $pay_amount = function_exists('bcsub') ? bcsub($amount, $coupon_amount, 2) : $amount - $coupon_amount;

        // 生成 1 到 9 之间的随机整数
        $randomCents = rand(1, 9);
        // 将随机整数转换为小数（0.01 到 0.09）
        $randomDecimal = $randomCents / 100;
        // 从原价中减去随机小数
        $pay_amount = function_exists('bcsub') ? bcsub($pay_amount, $randomDecimal, 2) : $pay_amount - $randomDecimal;

        if ($pay_amount <= 0) {
            $pay_amount = 0.01;
        }

        $ordersn = Random::ordersn();

        // 启动事务
        Db::beginTransaction();
        try {
            BoxOrder::create([
                'user_id' => $request->uid,
                'box_id' => $box->id,
                'amount' => $amount,
                'pay_amount' => $pay_amount,
                'coupon_amount' => $coupon_amount,
                'ordersn' => $ordersn,
                'coupon_id' => $coupon_id,
                'times' => $times
            ]);
            $ret = Pay::pay($pay_amount, $ordersn, '购买盲盒', 'box', JwtToken::getUser()->openid);
            // 提交事务
            Db::commit();
        } catch (\Throwable $e) {
            // 回滚事务
            Db::rollBack();
            return $this->fail($e->getMessage());
        }
        return $this->success('成功', $ret);

    }


}
