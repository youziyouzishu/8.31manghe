<?php

namespace app\controller;

use app\service\Coupon;
use app\service\Pay;
use app\tool\Random;
use plugin\admin\app\model\Box;
use plugin\admin\app\model\BoxLevel;
use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\model\UsersLevel;
use plugin\admin\app\model\UsersCoupon;


use plugin\admin\app\model\UsersPrize;
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

        if ($box->type == 4){
            return $this->fail('不属于普通盲盒');
        }

        $grades = $box
            ->prize()
            ->whereNot('grade',1)
            ->orderByDesc('grade')
            ->distinct()
            ->pluck('grade')
            ->values();

        $prizeData = $grades->map(function ($grade) use ($id) {
            $prizes = BoxPrize::where(['box_id' => $id, 'grade' => $grade])->get();
            return [
                'name' => (new BoxPrize())->getGradeList()[$grade],
                'chance' => $prizes->sum('chance'),
                'prize' => $prizes,
            ];
        });

        // 将 prize 数据嵌套在 box 对象中
        $box->grade = $prizeData;
        return $this->success('成功', $box);
    }

    function level(Request $request)
    {
        $id = $request->get('id');
        $box = Box::find($id);
        if (empty($box)) {
            return $this->fail('盲盒不存在');
        }

        if ($box->type != 4) {
            return $this->fail('不属于闯关盲盒');
        }

        $ulevel = UsersLevel::where(['user_id' => $request->uid,'box_id' => $id])->first();
        if (empty($ulevel)){
            //第一次进入当前闯关盲盒 初始化用户关卡数据
            UsersLevel::create([
                'user_id' => $request->uid,
                'box_id' => $id,
                'level_id' => UsersLevel::where('box_id', $id)->orderBy('name')->first()->id,
            ]);
        }

        /** todo 关卡 */
        $level = $box->level()->orderBy('name')->get()->each(function (BoxLevel $item) use ($request) {
            if (UsersLevel::where(['user_id' => $request->uid, 'level_id' => $item->id])->exists()) {
                $item->pass = 1;
            } else {
                $item->pass = 0;
            }
            $ticket_num = UsersPrize::where(['user_id' => $request->uid])
                ->whereIn('prize_id', $item->prize()->where(['grade' => 1])->pluck('id'))
                ->count();
            $item->ticket_num = $ticket_num;
        });

        return $this->success('成功', $level);
    }

    function level_prize(Request $request)
    {
        $id = $request->get('id');
        $level = BoxLevel::with(['box'])->find($id);
        if (!$level) {
            return $this->fail('关卡不存在');
        }

        $grades = $level
            ->prize()
            ->orderByDesc('grade')
            ->distinct()
            ->pluck('grade')
            ->values();

        $prizeData = $grades->map(function ($grade) use ($id) {
            $prizes = BoxPrize::where(['level_id' => $id, 'grade' => $grade])->get();
            return [
                'name' => (new BoxPrize())->getGradeList()[$grade],
                'chance' => $prizes->sum('chance'),
                'prize' => $prizes,
            ];
        });
        // 将 prize 数据嵌套在 box 对象中
        $level->grade = $prizeData;

        return $this->success('成功', $level);

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

    function next_level(Request $request)
    {
        $box_id = $request->post('box_id'); //盲盒ID
        $level_id = $request->post('level_id'); //关卡ID

        $level = BoxLevel::find($level_id);
        if (empty($level)){
            return $this->fail('关卡不存在');
        }
        if (!$level->box || $level->box->id != $box_id){
            return $this->fail('关卡与盲盒不匹配');
        }

        $user_level = UsersLevel::where(['user_id' => $request->uid, 'level_id' => $level_id])->first();

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
        $level_id = $request->post('level_id', 0);
        $box = Box::find($id);
        if (empty($box)) {
            return $this->fail('盲盒不存在');
        }
        if (!empty($level_id)){
            $level = BoxLevel::find($level_id);
            if (!$level){
                return $this->fail('关卡不存在');
            }
            if ($level->box->id != $id){
                return $this->fail('关卡与盲盒不匹配');
            }
            if (!UsersLevel::where(['user_id' => $request->uid, 'level_id' => $level_id])->exists()) {
                return $this->fail('未解锁该关卡');
            }
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
                'times' => $times,
                'level_id'=>$level_id
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
