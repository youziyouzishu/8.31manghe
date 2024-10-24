<?php

namespace app\controller;

use app\service\Pay;
use app\tool\Random;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\DeliverDetail;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
use support\Db;
use support\Request;
use Tinywan\Jwt\JwtToken;

class PrizeController extends BaseController
{

    function dissolve(Request $request)
    {
        $ids = $request->post('ids');
        UsersPrize::whereIn('id', explode(',', $ids))
            ->where(['user_id' => $request->uid, 'safe' => 0])
            ->get()
            ->each(function (UsersPrize $item) use ($request) {
                //执行删除
                $item->forceDelete();
                //增加水晶
                User::money($item->boxPrize->price, $request->uid, '分解获得');
            });
        return $this->success();
    }

    function give(Request $request)
    {
        $ids = $request->post('ids');
        $to_user_id = $request->get('to_user_id');
        $to_user = User::find($to_user_id);
        if (!$to_user) {
            return $this->fail('转增对象不存在');
        }
        $user = User::find($request->uid);
        if ($user->kol == 1){
            return $this->fail('达人不能转赠');
        }

        UsersPrize::whereIn('id', explode(',', $ids))
            ->where(['user_id' => $request->uid, 'safe' => 0])
            ->get()
            ->each(function (UsersPrize $item) use ($request, $to_user_id, $to_user) {
                //转增
                $item->user_id = $to_user_id;
                $item->mark = $item->user->nickname . '赠送';
                $item->save();
                //记录
                UsersPrizeLog::create([
                    'type' => 2,
                    'source_user_id'=> $request->uid,
                    'user_id' => $to_user_id,
                    'box_prize_id' => $item->box_prize_id,
                    'memo' => $item->user->nickname . ' 赠送'
                ]);
                UsersPrizeLog::create([
                    'type' => 1,
                    'source_user_id' => $to_user_id,
                    'user_id' => $request->uid,
                    'box_prize_id' => $item->box_prize_id,
                    'memo' => '赠送 ' . $to_user->nickname
                ]);
            });
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
        $ids = $request->post('ids');
        $freight = 0;
        $data = [];
        $rows = UsersPrize::whereIn('id', explode(',', $ids))
            ->where(['user_id' => $request->uid])
            ->get();
        $rows->each(function (UsersPrize $item) use (&$freight, &$data) {
            if ($item->boxPrize->price < 30) {
                $freight += 10;
            }
            $item->boxPrize->freight = $item->boxPrize->price < 30 ? 10 : 0;
            $data['boxPrize'][] = $item->boxPrize;
        });
        $data['freight'] = $freight;
        return $this->success('成功', $data);
    }

    function deliver(Request $request)
    {
        $ids = $request->post('ids');
        $address_id = $request->post('address_id');
        $ids = explode(',', $ids);
        if (empty($ids)){
            return $this->fail('请选择奖品');
        }

        $user = User::find($request->uid);
        if ($user->kol == 1){
            return $this->fail('达人不能发货');
        }

        $ordersn = Random::ordersn();

        $freight = 0;
        $rows = UsersPrize::whereIn('id', $ids)
            ->where(['user_id' => $request->uid])
            ->get();
        $rows->each(function (UsersPrize $item) use (&$freight) {
            if ($item->boxPrize->price < 30) {
                $freight += 10;
            }
        });
        $deliver = Deliver::create([
            'user_id' => $request->uid,
            'ordersn' => $ordersn,
            'freight' => $freight,
            'address_id' => $address_id,
        ]);
        if ($freight == 0) {
            $deliver->status = 1;
            $deliver->save();
            return $this->success();
        } else {

            $rows->each(function (UsersPrize $item) use ($deliver) {
                $deliver->detail()->create([
                    'box_prize_id' => $item->box_prize_id,
                    'user_prize_id' => $item->id
                ]);
            });

            $user = User::find($request->uid);
            if ($user->money >= $freight) {
                $ret = [];
                User::money(-$freight, $request->uid, '支付运费');
                $code = 3;
                $msg = '支付成功';

                // 创建一个新的请求对象 直接调用支付
                $notify = new NotifyController();
                $request->set([
                    '_data' => [
                        'get' => ['paytype' => 'balance', 'out_trade_no' => $ordersn, 'attach' => 'freight']
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
                $ret = Pay::pay($freight, $ordersn, '支付运费', 'freight', JwtToken::getUser()->openid);
                $code = 4;
                $msg = '开始微信支付';
            }
            return $this->json($code, $msg, $ret);
        }


    }
}
