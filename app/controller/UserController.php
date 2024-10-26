<?php

namespace app\controller;

use app\tool\Random;
use EasyWeChat\MiniApp\Application;
use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\DeliverDetail;
use plugin\admin\app\model\GoodsOrder;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersCoupon;
use plugin\admin\app\model\UsersDrawLog;
use plugin\admin\app\model\UsersLayer;
use plugin\admin\app\model\UsersMoneyLog;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
use support\Request;
use Tinywan\Jwt\JwtToken;

class UserController extends BaseController
{
    protected array $noNeedLogin = ['login'];

    function login(Request $request)
    {
        // 处理父级关系
        $parentInviteCode = $request->post('invitecode');
        $parent = User::where('invitecode', $parentInviteCode)->first();
        try {
            $app = new Application(config('wechat'));
            $res = $app->getUtils()->codeToSession((string)$request->post('code'));
            $openid = $res['openid'];
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        $user = User::where('openid', $openid)->first();

        if (!$user) {
            // 获取下一个自增ID
            do {
                $invitecode = Random::alnum();
            } while (User::where(['invitecode' => $invitecode])->exists());

            $nextId = User::max('id') + 1;
            $userData = [
                'nickname' => '昵称' . $nextId,
                'avatar' => '/app/admin/upload/files/20241014/670c7690a977.jpg',
                'openid' => $openid,
                'join_time' => date('Y-m-d H:i:s'),
                'join_ip' => $request->getRealIp(),
                'last_time' => date('Y-m-d H:i:s'),
                'last_ip' => $request->getRealIp(),
                'invitecode' => $invitecode
            ];
            if ($parent){
                $userData['parent_id'] = $parent->id;
            }
            // 创建新用户
            $user = User::create($userData);



            if ($parent) {
                // 增加直推关系
                UsersLayer::create([
                    'user_id' => $user->id,
                    'parent_id' => $parent->id,
                    'level' => 1
                ]);

                // 处理多层关系
                $positions = UsersLayer::where('user_id', $parent->id)->get();
                if ($positions->isNotEmpty()) {
                    foreach ($positions as $position) {
                        UsersLayer::create([
                            'user_id' => $user->id,
                            'parent_id' => $position->parent_id,
                            'layer' => $position->layer + 1
                        ]);
                    }
                }
            }
        } else {
            if ($user->status == 1){
                return $this->fail('账号已被禁用');
            }
            // 更新现有用户的最后登录时间和IP
            $user->last_time = date('Y-m-d H:i:s');
            $user->last_ip = $request->getRealIp();
            $user->save();
        }
        $user->client = JwtToken::TOKEN_CLIENT_MOBILE;
        $token = JwtToken::generateToken($user->toArray());
        return $this->success('成功', ['token' => $token]);
    }

    function getinfo(Request $request)
    {
        $row = User::find($request->uid);
        return $this->success('成功', $row);
    }

    function boxPrize(Request $request)
    {
        $safe = $request->post('safe', 0);

        $rows = UsersPrize::where(['user_id' => $request->uid, 'safe' => $safe])
            ->paginate()
            ->getCollection()
            ->pluck('boxPrize');
        return $this->success('成功', $rows);
    }

    function deliverList(Request $request)
    {
        $status = $request->post('status', 0);

        $rows = Deliver::with(['detail'])
            ->where(['user_id' => $request->uid])
            ->when(!empty($status), function (Builder $query) use ($status) {
                if ($status == 1) {
                    $query->where('status', 2);
                }
                if ($status == 2) {
                    $query->where('status', 3);
                }
                if ($status == 3) {
                    $query->where('status', 4);
                }
            })
            ->paginate()
            ->items();
        return $this->success('成功', $rows);
    }

    function getDeliverInfo(Request $request)
    {
        $deliver_id = $request->post('deliver_id');
        $row = Deliver::with(['detail'=>function (DeliverDetail $builder) {
            $builder->with('boxPrize');
        }, 'address'])
            ->where(['user_id' => $request->uid, 'id' => $deliver_id])
            ->first();
        return $this->success('成功', $row);
    }

    function confirmReceipt(Request $request)
    {
        $deliver_id = $request->post('deliver_id');
        $row = Deliver::where(['user_id' => $request->uid, 'id' => $deliver_id])->first();
        $row->status = 3;
        $row->save();
        return $this->success();
    }


    function editAvatar(Request $request)
    {
        $avatar = $request->post('avatar');
        User::where(['id' => $request->uid])->update(['avatar' => $avatar]);
        return $this->success();
    }

    function editNickname(Request $request)
    {
        $nickname = $request->post('nickname');
        User::where(['id' => $request->uid])->update(['nickname' => $nickname]);
        return $this->success();
    }

    function getMoneyLog(Request $request)
    {
        $month = $request->post('month', date('Y-m'));
        $rows = UsersMoneyLog::where(['user_id' => $request->uid])
            ->whereMonth('created_at', $month)
            ->orderByDesc('id')
            ->paginate()
            ->getCollection()
            ->each(function (UsersMoneyLog $item) {
                if ($item->money >= 0) {
                    $item->money = '+' . $item->money;
                } else {
                    $item->money = '-' . $item->money;
                }
            });
        return $this->success('成功', $rows);
    }

    function giveLog(Request $request)
    {
        $month = $request->post('month', date('Y-m'));
        $rows = UsersPrizeLog::with(['boxPrize', 'sourceUser'])
            ->where(['user_id' => $request->uid, 'type' => 1])
            ->whereMonth('created_at', $month)
            ->orderByDesc('id')
            ->paginate()
            ->items();

        return $this->success('成功', $rows);
    }

    function receiveLog(Request $request)
    {
        $month = $request->post('month', date('Y-m'));
        $rows = UsersPrizeLog::with(['boxPrize', 'sourceUser'])
            ->where(['user_id' => $request->uid, 'type' => 2])
            ->whereMonth('created_at', $month)
            ->orderByDesc('id')
            ->paginate()
            ->items();

        return $this->success('成功', $rows);
    }

    function consumeLog(Request $request)
    {
        $type = $request->post('type');
        if ($type == 1) {
            $rows = UsersDrawLog::with(['box'])
                ->where(['user_id' => $request->uid])
                ->paginate()
                ->items();
        } elseif ($type == 2) {
            $rows = GoodsOrder::where(['user_id' => $request->uid, 'status' => 2])
                ->paginate()
                ->getCollection()
                ->map(function (GoodsOrder $order) {
                    return [
                        'boxPrize' => $order->goods->boxPrize,
                        'ordersn' => $order->ordersn,
                    ];
                });
        } else {
            return $this->fail('参数错误');
        }
        return $this->success('成功', $rows);
    }

    function couponList(Request $request)
    {
        $status = $request->post('status');# 状态:1=未使用,2=已使用,3=已过期
        $rows = UsersCoupon::where(['user_id' => $request->uid, 'status' => $status])
            ->paginate()
            ->getCollection()
            ->pluck('coupon');
        return $this->success('成功', $rows);
    }


}
