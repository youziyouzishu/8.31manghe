<?php

namespace app\controller;

use app\service\Pay;
use Illuminate\Support\Collection;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\DeliverDetail;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersDisburse;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
use support\Db;
use support\Request;
use Tinywan\Jwt\JwtToken;

class PrizeController extends BaseController
{

    function dissolve(Request $request)
    {
        $prizes = $request->post('prizes');
        if (empty($prizes)) {
            return $this->fail('请选择要分解的奖品');
        }
        foreach ($prizes as $prize) {
            $res = UsersPrize::find($prize['id']);
            if (!$res) {
                return $this->fail('奖品不存在');
            }
            if ($res->safe == 1) {
                return $this->fail('奖品已锁定，不能分解');
            }
            if ($prize['num'] <= 0) {
                return $this->fail('请输入正确的数量');
            }
            if ($res->num < $prize['num']) {
                return $this->fail('奖品数量不足');
            }
            $res->decrement('num', $prize['num']);
            if ($res->num <= 0) {
                $res->delete();
            }
            User::money($res->price * $prize['num'], $request->uid, '退货' . $res->boxPrize->name . '获得');
        }
        return $this->success();
    }

    function give(Request $request)
    {
        $prizes = $request->post('prizes');

        $to_user_id = $request->post('to_user_id');
        $to_user = User::find($to_user_id);
        if (!$to_user) {
            return $this->fail('转增对象不存在');
        }
        $user = User::find($request->uid);
        if ($user->kol == 1) {
            return $this->fail('错误');
        }
        $xiaofei = UsersDisburse::where(['user_id' => $request->uid, 'type' => 1])->sum('amount');
        if ($xiaofei < 50) {
            return $this->fail('转赠失败');
        }

        foreach ($prizes as $prize) {
            $res = UsersPrize::find($prize['id']);
            if ($res->safe == 1) {
                return $this->fail('奖品已锁定，不能赠送');
            }
            if ($prize['num'] <= 0) {
                return $this->fail('请输入正确的数量');
            }
            if ($res->num < $prize['num']) {
                return $this->fail('奖品数量不足');
            }
            if ($touserprize = UsersPrize::where(['user_id' => $to_user_id, 'box_prize_id' => $res->box_prize_id, 'price' => $res->price])->first()) {
                $touserprize->increment('num', $prize['num']);
            } else {
                UsersPrize::create([
                    'user_id' => $to_user_id,
                    'box_prize_id' => $res->box_prize_id,
                    'price' => $res->price,
                    'num' => $prize['num'],
                    'mark' => $user->nickname . '赠送',
                    'grade' => $res->grade,
                ]);
            }

            //记录
            UsersPrizeLog::create([
                'type' => 2,
                'source_user_id' => $request->uid,
                'user_id' => $to_user_id,
                'box_prize_id' => $res->box_prize_id,
                'mark' => $user->nickname . ' ' . $user->id . ' 赠送',
                'price' => $res->price,
                'grade' => $res->boxPrize->grade,
                'num' => $prize['num']
            ]);
            UsersPrizeLog::create([
                'type' => 1,
                'source_user_id' => $to_user_id,
                'user_id' => $request->uid,
                'box_prize_id' => $res->box_prize_id,
                'mark' => '赠送',
                'price' => $res->price,
                'grade' => $res->boxPrize->grade,
                'num' => $prize['num']
            ]);

            $res->decrement('num', $prize['num']);
            if ($res->num <= 0) {
                $res->delete();
            }
        }
        return $this->success();
    }

    function changesafe(Request $request)
    {
        $ids = $request->post('ids');
        $safe = $request->post('safe');
        $rows = UsersPrize::whereIn('id', explode(',', $ids))
            ->where(['user_id' => $request->uid, 'safe' => $safe == 0 ? 1 : 0])
            ->get()
            ->each(function (UsersPrize $item) use ($request, $safe) {
                $item->safe = $safe;
                $item->save();
            });
        return $this->success();
    }

    function getPrizesFreight(Request $request)
    {
        $prizes = $request->post('prizes');
        $prize = $prizes[0];
        $freight = 0;

        $res = UsersPrize::with(['boxPrize'])->find($prize['id']);
        if (!$res) {
            return $this->fail('奖品不存在');
        }
        if ($res->price < 30) {
            $this_freight = 10 * $prize['num'];

        } else {
            $this_freight = 0;
        }
        $res->num = $prize['num'];
        $res->setAttribute('freight',$this_freight);
        $freight += $this_freight;
        $data['prizes'][] = $res;
        $data['freight'] = $freight;
        return $this->success('成功', $data);
    }

    #发货
    function deliver(Request $request)
    {
        $prizes = $request->post('prizes');#array

        $prize = $prizes[0];
        if (empty($prize)) {
            return $this->fail('请选择发货赏品');
        }
        $address_id = $request->post('address_id');
        if (empty($address_id)) {
            return $this->fail('请选择收货地址');
        }
        $user = User::find($request->uid);
        if ($user->kol == 1) {
            return $this->fail('错误');
        }
        $ordersn = Util::ordersn();


        $res = UsersPrize::find($prize['id']);
        if (!$res) {
            return $this->fail('奖品不存在');
        }
        if ($res->safe == 1) {
            return $this->fail('奖品已锁定，不能发货');
        }
        if ($prize['num'] <= 0 || $prize['num'] > $res->num) {
            return $this->fail('请输入正确的数量');
        }
        if ($res->price < 30) {
            $freight = 10 * $prize['num'];
        } else {
            $freight = 0;
        }
        $deliver = Deliver::create([
            'user_id' => $request->uid,
            'ordersn' => $ordersn,
            'pay_amount' => $freight,
            'address_id' => $address_id,
            'box_prize_id' => $res->box_prize_id,
            'user_prize_id' => $res->id,
            'num' => $prize['num'],
            'price' => $res->price,
            'grade' => $res->grade
        ]);

        if ($freight == 0) {
            // 创建一个新的请求对象 直接调用支付
            $notify = new NotifyController();
            $request->set('get', ['paytype' => 'balance', 'out_trade_no' => $ordersn, 'attach' => 'freight']);
            $res = $notify->balance($request);
            $res = json_decode($res->rawBody());
            if ($res->code == 1) {
                return $this->fail($res->msg);
            }
            $code = 3;
            $ret = [];
        } else {
            $user = User::find($request->uid);
            if ($user->money >= $freight) {

                $deliver->pay_type = 2;
                $deliver->save();
                $ret = [];
                User::money(-$freight, $request->uid, '支付运费');
                $code = 3;
                // 创建一个新的请求对象 直接调用支付
                $notify = new NotifyController();
                $request->set('get', ['paytype' => 'balance', 'out_trade_no' => $ordersn, 'attach' => 'freight']);
                $res = $notify->balance($request);
                $res = json_decode($res->rawBody());
                if ($res->code == 1) {
                    return $this->fail($res->msg);
                }
            } else {
                $ret = ['scene'=>'freight','ordersn'=>$ordersn];
                $code = 4;
            }
        }
        return $this->success('成功', [
            'code' => $code,
            'ret' => $ret
        ]);


    }
}
