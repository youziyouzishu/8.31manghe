<?php

namespace app\controller;

use app\library\Sms;
use Carbon\Carbon;
use EasyWeChat\MiniApp\Application;
use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\Coupon;
use plugin\admin\app\model\Deliver;
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
        $login_type = $request->post('login_type'); # 1微信登录 2手机号
        $code = $request->post('code');
        $mobile = $request->post('mobile');
        $captcha = $request->post('captcha');
        $openid = '';
        if ($login_type == 1) {
            try {
                $app = new Application(config('wechat'));
                $res = $app->getUtils()->codeToSession($code);
                $openid = $res['openid'];
            } catch (\Throwable $e) {
                return $this->fail($e->getMessage());
            }
            $user = User::where('openid', $openid)->first();
        } else {
            $captchaResult = Sms::check($mobile, $captcha, 'login');
            if (!$captchaResult) {
                return $this->fail('验证码错误');
            }
            $user = User::where('mobile', $mobile)->first();
        }
        dump($parentInviteCode);
        if (!empty($parentInviteCode)) {
            $parent = User::where('invitecode', $parentInviteCode)->first();
        } else {
            $parent = null;
        }
        $inviteCode = Util::createInvitecode();
        if (!$user) {
            // 获取下一个自增ID
            $nextId = User::max('id') + 1;
            $userData = [
                'nickname' => '新用户' . $nextId,
                'avatar' => '/app/admin/upload/files/20241205/675118b32fcb.jpg',
                'openid' => $openid ?? '',
                'mobile' => $mobile ?? '',
                'join_time' => date('Y-m-d H:i:s'),
                'join_ip' => $request->getRealIp(),
                'last_time' => date('Y-m-d H:i:s'),
                'last_ip' => $request->getRealIp(),
                'invitecode' => $inviteCode
            ];
            if ($parent) {
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
                            'level' => $position->layer + 1
                        ]);
                    }
                }
            }
        } else {
            if ($user->status == 1) {
                return $this->fail('账号已被禁用');
            }
            // 更新现有用户的最后登录时间和IP
            $user->last_time = date('Y-m-d H:i:s');
            $user->last_ip = $request->getRealIp();
            $user->save();
        }
        $user->client = JwtToken::TOKEN_CLIENT_MOBILE;
        $token = JwtToken::generateToken($user->toArray());
        return $this->success('成功', ['token' => $token, 'user' => $user]);
    }

    function getinfo(Request $request)
    {

        $row = User::find($request->uid);
        return $this->success('成功', $row);
    }

    function boxPrize(Request $request)
    {
        $safe = $request->post('safe', 0);

        $rows = UsersPrize::where(['user_id' => $request->uid, 'safe' => $safe])->with(['boxPrize' => function ($query) {
            $query->withTrashed();
        }])
            ->orderByDesc('price')
            ->paginate()
            ->items();
        return $this->success('成功', $rows);
    }

    function deliverList(Request $request)
    {
        $status = $request->post('status', 0);
        $rows = Deliver::where(['user_id' => $request->uid])
            ->with(['boxPrize'])
            ->orderByDesc('id')
            ->when(!empty($status), function ($query) use ($status) {
                $query->where('status', $status);
            }, function ($query) {
                $query->whereIn('status', [1, 2, 3]);
            })
            ->paginate()
            ->items();
        return $this->success('成功', $rows);
    }

    function getDeliverInfo(Request $request)
    {
        $deliver_id = $request->post('deliver_id');
        $row = Deliver::with(['boxPrize', 'address'])->find($deliver_id);
        return $this->success('成功', $row);
    }


    function confirmReceipt(Request $request)
    {
        $deliver_id = $request->post('deliver_id');
        $row = Deliver::where(['user_id' => $request->uid, 'id' => $deliver_id])->first();
        if (!$row) {
            return $this->fail('数据不存在');
        }
        if ($row->status != 2) {
            return $this->fail('状态错误');
        }
        $row->status = 3;
        $row->complete_time = date('Y-m-d H:i:s');
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
        $month = $request->post('month');
        $date = Carbon::parse($month);
        // 提取年份和月份
        $year = $date->year;
        $month = $date->month;
        $rows = UsersMoneyLog::where(['user_id' => $request->uid])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderByDesc('id')
            ->paginate()
            ->getCollection()
            ->each(function (UsersMoneyLog $item) {
                if ($item->money >= 0) {
                    $item->money = '+' . $item->money;
                }
            });
        return $this->success('成功', $rows);
    }

    function giveLog(Request $request)
    {
        $month = $request->post('month', date('Y-m'));
        $date = Carbon::parse($month);
        // 提取年份和月份
        $year = $date->year;
        $month = $date->month;
        $rows = UsersPrizeLog::with(['boxPrize', 'sourceUser'])
            ->where(['user_id' => $request->uid, 'type' => 1])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderByDesc('id')
            ->paginate()
            ->items();

        return $this->success('成功', $rows);
    }

    function receiveLog(Request $request)
    {
        $month = $request->post('month', date('Y-m'));
        $date = Carbon::parse($month);
        // 提取年份和月份
        $year = $date->year;
        $month = $date->month;
        $rows = UsersPrizeLog::with(['boxPrize', 'sourceUser'])
            ->where(['user_id' => $request->uid, 'type' => 2])
            ->whereYear('created_at', $year)
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
                ->orderByDesc('id')
                ->paginate()
                ->items();
        } elseif ($type == 2) {
            $rows = GoodsOrder::where(['user_id' => $request->uid, 'status' => 2])
                ->orderByDesc('id')
                ->paginate()
                ->getCollection()
                ->map(function (GoodsOrder $order) {
                    // 使用 withTrashed 包含被软删除的 goods 数据
                    $goods = $order->goods()->withTrashed()->first();
                    $boxPrize = $goods ? $goods->boxPrize()->withTrashed()->first() : null;
                    return [
                        'boxPrize' => $boxPrize ?: null,
                        'ordersn' => $order->ordersn,
                        'id' => $order->id
                    ];
                });
        } else {
            return $this->fail('参数错误');
        }
        return $this->success('成功', $rows);
    }

    function consumeDetail(Request $request)
    {
        $type = $request->post('type');
        $id = $request->post('id');
        if ($type == 1) {
            $row = UsersDrawLog::with(['box', 'prizeLog' => function ($query) {
                $query->with(['boxPrize']);
            }, 'orders'])
                ->where(['user_id' => $request->uid, 'id' => $id])
                ->first();
        } elseif ($type == 2) {
            $row = GoodsOrder::with(['goods.boxPrize'])->where(['user_id' => $request->uid, 'id' => $id])
                ->first();
        } else {
            return $this->fail('参数错误');
        }
        return $this->success('成功', $row);
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

    function getUserInfoById(Request $request)
    {
        $user_id = $request->post('user_id');
        $row = User::select(['id', 'avatar', 'nickname'])->find($user_id);
        return $this->success('成功', $row);
    }

    function receive(Request $request)
    {
        $invitecode = $request->post('invitecode');
        $row = User::where(['invitecode' => $invitecode])->first();
        if (!$row) {
            return $this->fail('邀请码不存在');
        }
        $user = User::find($request->uid);
        if ($row->fuli == 1) {
            return $this->fail('不能重复领取');
        }

        if (($user->parent_id != 0 && $user->parent_id != $row->id) || $user->new != 1) {
            return $this->fail('不属于新用户');
        }
        $user->parent_id = $row->id;
        $user->fuli = 1;
        $user->save();
        $coupon = Coupon::where(['fuli' => 1])->get();
        foreach ($coupon as $item) {
            UsersCoupon::create([
                'user_id' => $request->uid,
                'coupon_id' => $item->id,
            ]);
        }
        return $this->success();
    }

    function changeMobile(Request $request)
    {
        $mobile = $request->post('mobile');
        $captcha = $request->post('captcha');
        $smsResult = Sms::check($mobile, $captcha, 'changemobile');
        if (!$smsResult) {
            return $this->fail('验证码错误');
        }
        $user = User::find($request->uid);
        $user->mobile = $mobile;
        $user->username = $mobile;
        $user->save();
        return $this->success();
    }

    function bindMobile(Request $request)
    {
        $code = $request->post('code');
        //小程序
        $app = new Application(config('wechat'));
        $api = $app->getClient();
        $ret = $api->postJson('/wxa/business/getuserphonenumber', [
            'code' => $code
        ]);
        $ret = json_decode($ret);
        if ($ret->errcode != 0) {
            return $this->fail('获取手机号失败');
        }
        $mobile = $ret->phone_info->phoneNumber;
        $row = User::find($request->uid);
        $row->mobile = $mobile;
        $row->username = $mobile;
        $row->save();
        return $this->success('成功');
    }


}
