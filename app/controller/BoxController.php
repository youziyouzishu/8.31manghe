<?php

namespace app\controller;

use app\service\Coupon;
use app\service\Pay;
use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\Box;
use plugin\admin\app\model\BoxLevel;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersCoupon;
use plugin\admin\app\model\UsersDrawLog;
use plugin\admin\app\model\UsersLevel;
use plugin\admin\app\model\UsersLevelLog;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
use support\Cache;
use support\Db;
use support\Request;
use Tinywan\Jwt\JwtToken;
use Webman\Push\Api;


class BoxController extends BaseController
{

    protected array $noNeedLogin = ['index', 'boxPrize', 'getDrawLog'];


    public function index(Request $request)
    {
        $type = $request->post('type', 1);
        $sort = $request->post('sort', 'desc');
        $rows = Box::where(['type' => $type, 'status' => 1])
            ->orderBy('price', $sort)
            ->paginate()
            ->items();
        return $this->success('成功', $rows);
    }

    function boxPrize(Request $request)
    {
        $box_id = $request->post('box_id');
        $box = Box::find($box_id);
        if (empty($box)) {
            return $this->fail('盲盒不存在');
        }

        if ($box->type == 4) {
            return $this->fail('不属于普通盲盒');
        }
        $grades = $box
            ->boxPrize()
            ->whereNot('grade', 1)
            ->orderByDesc('grade')
            ->distinct()
            ->pluck('grade')
            ->values();
        $prizeData = [];
        $grades->each(function ($grade) use ($box_id, &$prizeData) {
            $prizes = BoxPrize::where(['box_id' => $box_id, 'grade' => $grade])->get();
            $prizeData[] = [
                'name' => $grade,
                'chance' => round($prizes->sum('chance'), 3),
                'boxPrize' => $prizes,
            ];
        });
        // 将 boxPrize 数据嵌套在 box 对象中
        $box->list = $prizeData;
        return $this->success('成功', $box);
    }

    function level(Request $request)
    {
        $box_id = $request->post('box_id');
        $box = Box::find($box_id);
        if (empty($box)) {
            return $this->fail('盲盒不存在');
        }

        if ($box->type != 4) {
            return $this->fail('不属于闯关盲盒');
        }

        $ulevel = UsersLevel::where(['user_id' => $request->uid, 'box_id' => $box_id])->first();
        if (empty($ulevel)) {
            //第一次进入当前闯关盲盒 初始化用户关卡数据
            $firstLevel = BoxLevel::getFirstLevel($box_id);
            UsersLevel::create([
                'user_id' => $request->uid,
                'box_id' => $box_id,
                'level_id' => $firstLevel->id
            ]);
            UsersLevelLog::create([
                'user_id' => $request->uid,
                'box_id' => $box_id,
                'level_id' => $firstLevel->id
            ]);
        }
        $level = $box->level()->orderBy('name')->get()->each(function (BoxLevel $item) use ($request) {
            if (UsersLevelLog::where(['level_id' => $item->id, 'user_id' => $request->uid])->exists()) {
                $item->pass = true;
            } else {
                $item->pass = false;
            }
            $item->ticket_count = UsersPrize::getUserPresentLevelTicketCount($item->box_id, $item->name, $request->uid);
        });
        return $this->success('成功', $level);
    }

    function levelPrize(Request $request)
    {
        $level_id = $request->post('level_id');
        $level = BoxLevel::with(['box'])->find($level_id);
        $parentlevel = BoxLevel::where(['box_id' => $level->box_id])->where('name', '<', $level->name)->exists();

        if (!$level) {
            return $this->fail('关卡不存在');
        }


        $grades = $level
            ->boxPrize()
            ->orderByDesc('grade')
            ->distinct()
            ->pluck('grade')
            ->values();

        $prizeData = []; // 初始化 prizeData 数组

        $grades->each(function ($grade) use ($level_id, &$prizeData) {
            $prizes = BoxPrize::where(['level_id' => $level_id, 'grade' => $grade])->get();
            $prizeData[] = [
                'name' => $grade,
                'chance' => round($prizes->sum('chance'), 3),
                'boxPrize' => $prizes,
            ];
        });

        // 将 boxPrize 数据嵌套在 level 对象中
        $level->grade = $prizeData;
        $level->hasparent = $parentlevel;
        $ticket_count = UsersPrize::getUserPresentLevelTicketCount($level->box_id, $level->name, $request->uid);
        if ($ticket_count > 0 && !UsersLevelLog::existsUsersLevelLog($level_id, $request->uid)) {
            //如果查看的关卡是未通关并且有通关票  则进入这一关
            $usersLevel = UsersLevel::where(['user_id' => $request->uid, 'box_id' => $level->box_id])->first();
            $usersLevel->level_id = $level_id;
            $usersLevel->save();
            UsersLevelLog::create([
                'user_id' => $request->uid,
                'box_id' => $level->box_id,
                'level_id' => $level_id
            ]);
        }


        return $this->success('成功', $level);

    }

    #满足条件优惠券
    function canuseCoupon(Request $request)
    {
        $box_id = $request->post('box_id');
        $times = $request->post('times');
        $box = Box::find($box_id);
        if (!$box) {
            return $this->fail('盲盒不存在');
        }

        $amount = $box->price * $times; #需要支付金额

        $rows = UsersCoupon::with(['coupon'])->where(['user_id' => $request->uid, 'status' => 1])
            ->whereDoesntHave('coupon', function ($query) use ($amount) {
                $query->where([
                    ['type', '=', 2],
                    ['with_amount', '>', $amount]
                ]);
            })
            ->get();

        return $this->success('成功', $rows);

    }


    function getPrice(Request $request)
    {
        $box_id = $request->post('box_id');
        $times = $request->post('times');
        $coupon_id = $request->post('coupon_id', 0);
        $row = Box::find($box_id);
        if (!$row) {
            return $this->fail('盲盒不存在');
        }
        $amount = $row->price * $times;
        $coupon_amount = Coupon::getCouponAmount($amount, $coupon_id);

        $pay_amount = function_exists('bcsub') ? bcsub($amount, $coupon_amount, 2) : $amount - $coupon_amount;

        if ($pay_amount <= 0) {
            $pay_amount = 0.01;
        }
        return $this->success('成功', ['pay_amount' => $pay_amount]);
    }

    /**
     * 抽奖和支付
     */
    function draw(Request $request)
    {
        $box_id = $request->post('box_id');
        $times = $request->post('times');
        $coupon_id = $request->post('coupon_id', 0);
        $level_id = $request->post('level_id', 0);
        $box = Box::find($box_id);
        if (empty($box)) {
            return $this->fail('盲盒不存在');
        }

        try {
            if (!empty($level_id)) {
                $level = BoxLevel::find($level_id);
                $firstLevel = BoxLevel::getFirstLevel($box_id);
                $endLevel = BoxLevel::getEndLevel($box_id);

                if (!$level) {
                    return $this->fail('关卡不存在');
                }
                if ($level->box->id != $box_id) {
                    return $this->fail('关卡与盲盒不匹配');
                }
                if (!$firstLevel) {
                    return $this->fail('盲盒不存在关卡');
                }
                if (!$endLevel) {
                    return $this->fail('不存在最后一关');
                }
                if ($level->id != $firstLevel->id) {
                    //非第一关 进行抽奖
                    //找出上一关判断是否有这一关的通关券
                    $getLastLevel = BoxLevel::getLastLevel($box_id, $level->name);

                    $lastPrizes = $getLastLevel->boxPrize()->where(['grade' => 1])->pluck('id');//获取上一关通关券
                    $lastTicket = UsersPrize::where(['user_id' => $request->uid])->whereIn('box_prize_id', $lastPrizes)->get();//获取用户拥有的上一关通关券

                    $lastTicketCount = $lastTicket->sum('num');

                    if ($times > $lastTicketCount) {
                        return $this->fail('通关券不足');
                    }
                    //记录
                    $draw = UsersDrawLog::create(['user_id' => $request->uid, 'times' => $times, 'box_id' => $box_id, 'level_id' => $level_id, 'ordersn' => '']); #创建抽奖记录


                    $winnerPrize = ['gt_n' => 0, 'list' => []];
                    $user = User::find($request->uid);


                    for ($i = 0; $i < $times; $i++) {
                        #每次抽奖刷新盲盒数据
                        $level->refresh();
                        //开始抽奖
                        $prizes = BoxPrize::where(['level_id' => $level_id])
                            ->when($endLevel->id == $level_id, function ($query) use ($level,$user) {
                                //如果是普通用户才受奖金池限制
                                if ($user->kol == 0) {
                                    $query->whereBetween('price', [0, $level->box->pool_amount]);
                                }else{
                                    $query->whereBetween('price', [0, $level->box->kol_pool_amount]);
                                }
                            })
                            ->get();
                        // 如果没有可用奖品，返回提示
                        if ($prizes->isEmpty()) {
                            $prizes = BoxPrize::where(['level_id' => $level_id])->orderBy('price')->limit(3)->get();
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
                        //达人拥有额外的中奖率

                        $currentChance += $user->chance;

                        foreach ($prizes as $prize) {
                            $currentChance += $prize->chance;
                            if ($randomNumber < $currentChance) {
                                $winnerPrize['list'][] = $prize;
                                if ($prize->grade == 5) {
                                    $winnerPrize['gt_n'] = 1;
                                }
                                // 减少奖金池金额
                                if ($user->kol == 0) {
                                    $prize->box->decrement('pool_amount', $prize->price);
                                }else{
                                    $prize->box->decrement('kol_pool_amount', $prize->price);
                                }
                                //删除用户通关券
                                $ticket = $lastTicket->first(); // 返回第一个元素
                                if ($ticket) {
                                    $ticket->decrement('num');
                                    if ($ticket->num <= 0) {
                                        $ticket->delete();
                                        // 从集合中移除该元素
                                        $lastTicket->forget($ticket->getKey());
                                    }
                                }
                                break;
                            }
                        }
                    }
                    $online = Cache::has("private-user-{$request->uid}");
                    if (!$online) {
                        Cache::set("private-user-{$request->uid}-winner_prize", $winnerPrize);
                    } else {
                        // 初始化API客户端
                        $api = new Api(
                            'http://127.0.0.1:3232',
                            config('plugin.webman.push.app.app_key'),
                            config('plugin.webman.push.app.app_secret')
                        );
                        // 给客户端推送私有 prize_draw 事件的消息
                        $api->trigger("private-user-{$request->uid}", 'prize_draw', [
                            'winner_prize' => $winnerPrize
                        ]);
                    }


                    foreach ($winnerPrize['list'] as $item) {
                        // 发放奖品并且记录
                        if ($userPrize = UsersPrize::where(['user_id' => $request->uid, 'box_prize_id' => $item->id, 'price' => $item->price])->first()) {
                            $userPrize->increment('num');
                        } else {
                            UsersPrize::create([
                                'user_id' => $request->uid,
                                'box_prize_id' => $item->id,
                                'price' => $item->price,
                                'num' => 1,
                                'mark' => '抽奖获得',
                                'grade' => $item->grade,
                            ]);
                        }
                        UsersPrizeLog::create([
                            'draw_id' => $draw->id,
                            'user_id' => $request->uid,
                            'box_prize_id' => $item->id,
                            'mark' => '抽奖获得',
                            'price' => $item->price,
                            'type' => 0,
                            'grade' => $item->grade,
                            'num' => 1,
                        ]);
                    }

                    $ret = [];
                    return $this->success('成功', ['code' => 2, 'ret' => $ret]);
                }
            }


            $amount = $box->price * $times;
            $coupon_amount = Coupon::getCouponAmount($amount, $coupon_id);
            $pay_amount = function_exists('bcsub') ? bcsub($amount, $coupon_amount, 2) : $amount - $coupon_amount;
            $ordersn = Util::ordersn();
            $orderData = [
                'user_id' => $request->uid,
                'box_id' => $box->id,
                'amount' => $amount,
                'pay_amount' => 0,
                'coupon_amount' => $coupon_amount,
                'ordersn' => $ordersn,
                'user_coupon_id' => empty($coupon_id) ? 0 : $coupon_id,
                'times' => $times,
                'level_id' => $level_id
            ];
            $order = BoxOrder::create($orderData);
            //先用余额支付 余额不足再用微信支付
            $ret = [];
            $user = User::find($request->uid);
            if ($user->money >= $pay_amount) {
                if ($pay_amount <= 0) {
                    $pay_amount = 0;
                }
                $order->pay_amount = $pay_amount;
                $order->save();
                User::money(-$pay_amount, $request->uid, $box->name);
                $code = 3;
                // 创建一个新的请求对象 直接调用支付
                $notify = new NotifyController();
                $request->set('get', ['paytype' => 'balance', 'out_trade_no' => $ordersn, 'attach' => 'box']);
                $res = $notify->balance($request);
                $res = json_decode($res->rawBody());
                if ($res->code == 1) {
                    //支付失败
                    // 回滚事务
                    return $this->fail($res->msg);
                }
            } else {
                // 生成 1 到 9 之间的随机整数
                $randomCents = rand(1, 9);
                // 将随机整数转换为小数（0.01 到 0.09）
                $randomDecimal = $randomCents / 100;
                // 从原价中减去随机小数
                $pay_amount = function_exists('bcsub') ? bcsub($pay_amount, $randomDecimal, 2) : $pay_amount - $randomDecimal;
                if ($pay_amount <= 0) {
                    $pay_amount = 0.01;
                }
                $order->pay_amount = $pay_amount;
                $order->save();
                $ret = ['scene' => 'box', 'ordersn' => $ordersn];
                $code = 4;
            }
            // 提交事务
            return $this->success('成功', [
                'code' => $code,
                'ret' => $ret,
            ]);
        } catch (\Throwable $e) {
            // 回滚事务
            return $this->fail($e->getMessage());
        }
    }

    function prizeLog(Request $request)
    {
        $box_id = $request->post('box_id');
        $level_id = $request->post('level_id', 0);
        $box = Box::find($box_id);
        if (!$box) {
            return $this->fail('盲盒不存在');
        }
        if (!empty($level_id)) {
            $level = BoxLevel::find($level_id);
            if (!$level) {
                return $this->fail('关卡不存在');
            }
            if ($level->box_id != $box_id) {
                return $this->fail('盲盒关卡不存在');
            }
            $prize_ids = $level->boxPrize()->pluck('id');
        } else {
            $prize_ids = $box->boxPrize()->pluck('id');
        }
        $list = UsersPrizeLog::with(['boxPrize', 'user'])
            ->whereIn('box_prize_id', $prize_ids)
            ->where('type', 0)
            ->orderBy('id', 'desc')
            ->paginate()
            ->items();
        return $this->success('成功', $list);
    }


    #中奖记录
    function getDrawLog(Request $request)
    {
        $box_id = $request->post('box_id');
        $level_id = $request->post('level_id', 0);
        $grade = $request->post('grade');

        if (empty($box_id)) {
            return $this->fail('所选盲盒不能为空');
        }

        $prize_ids = BoxPrize::where(['box_id' => $box_id])
            ->when(!empty($level_id), function (Builder $builder) use ($level_id) {
                $builder->where('level_id', $level_id);
            })
            ->where('grade', $grade)
            ->pluck('id');
        $list = UsersPrizeLog::with(['user', 'boxPrize'])->whereIn('box_prize_id', $prize_ids)
            ->where('type', 0)
            ->orderBy('id', 'desc')
            ->paginate()
            ->getCollection()
            ->each(function ($item) use ($request, $grade,$box_id) {
                $last = UsersPrizeLog::where(['user_id' => $item->user_id, 'type' => 0])->whereHas('boxPrize', function ($query) use ($box_id) {
                    $query->where('box_id', $box_id);
                })->where('id', '<', $item->id)->orderByDesc('id')->where('grade', $grade)->first();
                if (!$last) {
                    $prizes = UsersPrizeLog::with(['user', 'boxPrize'])->where(['user_id' => $item->user_id, 'type' => 0])->whereHas('boxPrize', function ($query) use ($box_id) {
                        $query->where('box_id', $box_id);
                    })->where('id', '<', $item->id)->count();
                } else {
                    $prizes = UsersPrizeLog::with(['user', 'boxPrize'])->where(['user_id' => $item->user_id, 'type' => 0])->whereHas('boxPrize', function ($query) use ($box_id) {
                        $query->where('box_id', $box_id);
                    })->where('id', '<', $item->id)->where('id', '>', $last->id)->count();
                }
                $item->setAttribute('times', $prizes);
            });
        return $this->success('成功', $list);
    }


    #获取中奖记录等级
    function getGradeByDrawLog(Request $request)
    {
        $box_id = $request->post('box_id');
        $level_id = $request->post('level_id', 0);
        if (empty($box_id)) {
            return $this->fail('所选盲盒不能为空');
        }
        #盲盒内大于N赏的奖品
        $grade = BoxPrize::where(['box_id' => $box_id])
            ->when(!empty($level_id), function (Builder $builder) use ($level_id) {
                $builder->where('level_id', $level_id);
            })
            ->orderByDesc('grade')
            ->pluck('grade')
            ->unique()
            ->values();

        return $this->success('成功', $grade);
    }


}
