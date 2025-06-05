<?php

namespace app\controller;

use app\library\Sms;
use Carbon\Carbon;
use EasyWeChat\MiniApp\Application;
use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\Coupon;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\GoodsOrder;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersCoupon;
use plugin\admin\app\model\UsersDisburse;
use plugin\admin\app\model\UsersDrawLog;
use plugin\admin\app\model\UsersGaine;
use plugin\admin\app\model\UsersGiveLog;
use plugin\admin\app\model\UsersLayer;
use plugin\admin\app\model\UsersMoneyLog;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
use support\Cache;
use support\Db;
use support\Log;
use support\Request;
use Tinywan\Jwt\JwtToken;
use Webman\Push\Api;
use Webman\RedisQueue\Client;

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
                'avatar' => '/app/admin/upload/files/20250516/6826f3fc06c2.jpg',
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

        $row = User::find($request->user_id);
        return $this->success('成功', $row);
    }

    function boxPrize(Request $request)
    {
        $safe = $request->post('safe', 0);

        $rows = UsersPrize::where(['user_id' => $request->user_id, 'safe' => $safe])->with(['boxPrize' => function ($query) {
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
        $rows = Deliver::where(['user_id' => $request->user_id])
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
        $row = Deliver::where(['user_id' => $request->user_id, 'id' => $deliver_id])->first();
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
        User::where(['id' => $request->user_id])->update(['avatar' => $avatar]);
        return $this->success();
    }

    function editNickname(Request $request)
    {
        $nickname = $request->post('nickname');
        User::where(['id' => $request->user_id])->update(['nickname' => $nickname]);
        return $this->success();
    }

    function getMoneyLog(Request $request)
    {
        $month = $request->post('month');
        $date = Carbon::parse($month);
        // 提取年份和月份
        $year = $date->year;
        $month = $date->month;
        $rows = UsersMoneyLog::where(['user_id' => $request->user_id])
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
            ->where(['user_id' => $request->user_id, 'type' => 1])
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
            ->where(['user_id' => $request->user_id, 'type' => 2])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderByDesc('id')
            ->paginate()
            ->items();

        return $this->success('成功', $rows);
    }


    #赠送记录
    function giveLogV2(Request $request)
    {
        $rows = UsersGiveLog::with(['giveLog'=>function ($query) {
            $query->with('boxPrize');
        },'toUser'])
            ->withSum('giveLog as total_price',DB::raw('num * price'))
            ->where(['user_id' => $request->user_id])

            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return $this->success('成功', $rows);
    }


    #领取记录
    function receiveLogV2(Request $request)
    {

        $rows = UsersGiveLog::with(['receiveLog'=>function ($query) {
            $query->with('boxPrize');
        },'user'])
            ->withSum('receiveLog as total_price',DB::raw('num * price'))
            ->where(['to_user_id' => $request->user_id])
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return $this->success('成功', $rows);
    }

    function consumeLog(Request $request)
    {
        $type = $request->post('type');
        if ($type == 1) {
            $rows = UsersDrawLog::with(['box'])
                ->where(['user_id' => $request->user_id])
                ->orderByDesc('id')
                ->paginate()
                ->items();
        } elseif ($type == 2) {
            $rows = GoodsOrder::where(['user_id' => $request->user_id, 'status' => 2])
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
                ->where(['user_id' => $request->user_id, 'id' => $id])
                ->first();
        } elseif ($type == 2) {
            $row = GoodsOrder::with(['goods.boxPrize'])->where(['user_id' => $request->user_id, 'id' => $id])
                ->first();
        } else {
            return $this->fail('参数错误');
        }
        return $this->success('成功', $row);
    }

    function couponList(Request $request)
    {
        $status = $request->post('status');# 状态:1=未使用,2=已使用,3=已过期
        $rows = UsersCoupon::where(['user_id' => $request->user_id, 'status' => $status])
            ->paginate()
            ->items();
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


        $user = User::find($request->user_id);
        if ($user->fuli == 1) {
            return $this->fail('不能重复领取');
        }

        if (($user->parent_id != 0 && $user->parent_id != $row->id) || $user->new != 1) {
            return $this->fail('不属于新用户');
        }
        $user->parent_id = $row->id;
        $user->fuli = 1;
        $user->save();
        $coupon_id = UsersCoupon::where(['user_id' => $request->user_id])->distinct()->pluck('coupon_id');
        $coupon = Coupon::where(['fuli' => 1])->whereNotIn('id', $coupon_id)->get();
        foreach ($coupon as $item) {

            $expired_at = Carbon::now()->addDays($item->expired_day);

            $user_coupon = UsersCoupon::create([
                'user_id' => $request->user_id,
                'coupon_id' => $item->id,
                'name'=>$item->name,
                'type'=>$item->type,
                'amount'=>$item->amount,
                'with_amount'=>$item->with_amount,
                'expired_at'=>$expired_at->toDateTimeString()
            ]);

            Client::send('coupon-expire',['event'=>'user_coupon_expire','id'=>$user_coupon->id],$expired_at->timestamp - time());

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
        $user = User::find($request->user_id);
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
        $row = User::find($request->user_id);
        $row->mobile = $mobile;
        $row->username = $mobile;
        $row->save();
        return $this->success('成功');
    }

    /**
     * 获取宝箱
     */
    function getGaine(Request $request)
    {
        $rows = UsersGaine::with(['gaine'=>function ($query) {
            $query->with(['boxPrize']);
        }])->where(['user_id'=>$request->user_id])->get();
        return $this->success('成功',$rows);
    }

    function openGaine(Request $request)
    {
        $gaine_id = $request->post('gaine_id');
        dump($gaine_id);
        $row = UsersGaine::find($gaine_id);
        if (!$row) {
            return $this->fail('宝箱不存在');
        }
        $user = User::find($request->user_id);
        $box = $row->gaine->box;
        $prizes = $row->gaine->boxPrize()
            ->where(function ($query)use($user,$box){
                //如果是普通用户才受奖金池限制
                if ($user->kol == 0) {
                    $query->whereBetween('price', [0, $box->pool_amount]);
                } else {
                    $query->whereBetween('price', [0, $box->kol_pool_amount]);
                }
            })
            ->get();



        if ($prizes->isEmpty()) {
            $prizes = $row->gaine->boxPrize()->orderBy('price')->limit(3)->get();
            if ($prizes->isEmpty()) {
                return $this->fail('奖品不足');
            }
        }

        // 计算总概率
        $totalChance = $prizes->sum('chance');
        // 生成一个介于 0 和总概率之间的随机数
        $randomNumber = mt_rand() / mt_getrandmax() * $totalChance;
        // 累加概率，确定中奖奖品
        $currentChance = 0.0;
        // 用户可能单独增加额外的概率
        $currentChance += $user->chance;

        // 对奖品列表进行随机排序
        $prizes = $prizes->shuffle();
        $winnerPrize = ['gt_n' => 0, 'list' => []];
        foreach ($prizes as $prize) {
            $currentChance += $prize->chance;
            if ($randomNumber <= $currentChance) {
                $winnerPrize['list'][] = $prize;
                if ($prize->grade == 5) {
                    $winnerPrize['gt_n'] = 1;
                }
                if ($user->kol == 0) {
                    //普通用户才增加奖金池
                    // 增加奖金池金额
                    $box->decrement('pool_amount', $prize->price);
                } else {
                    $box->decrement('kol_pool_amount', $prize->price);
                }
                break;
            }
        }

        $online = Cache::has("private-user-{$user->id}");
        dump('在线状态：'.$online);
        if (!$online) {
            Cache::set("private-user-{$user->id}-winner_prize", $winnerPrize);
        } else {
            $api = new Api(
                'http://127.0.0.1:3232',
                config('plugin.webman.push.app.app_key'),
                config('plugin.webman.push.app.app_secret')
            );
            // 给客户端推送私有 prize_draw 事件的消息
            $api->trigger("private-user-{$user->id}", 'prize_draw', [
                'winner_prize' => $winnerPrize
            ]);
            dump("推送消息成功:private-user-{$user->id}-winner_prize");
            Log::info("推送消息成功:private-user-{$user->id}-winner_prize");
        }


        foreach ($winnerPrize['list'] as $item) {
            // 发放奖品并且记录
            if ($userPrize = UsersPrize::where(['user_id' => $user->id, 'box_prize_id' => $item->id, 'price' => $item->price])->first()) {
                $userPrize->increment('num');
            } else {
                UsersPrize::create([
                    'user_id' => $user->id,
                    'box_prize_id' => $item->id,
                    'price' => $item->price,
                    'num' => 1,
                    'mark' => '宝箱抽奖获得',
                    'grade' => $item->grade,
                ]);
            }
            UsersPrizeLog::create([
                'draw_id' => $row->draw_id,
                'user_id' => $user->id,
                'box_prize_id' => $item->id,
                'mark' => '宝箱抽奖获得',
                'price' => $item->price,
                'type' => 14,
                'grade' => $item->grade,
                'num' => 1,
            ]);
        }
        dump($winnerPrize['list'][0]['id']);
        dump($winnerPrize['list'][0]['grade']);
        dump($winnerPrize['list'][0]['name']);
        $row->delete();
        return $this->success('成功');
    }

}
