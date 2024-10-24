<?php

namespace app\controller;

use app\service\Coupon;
use app\service\Pay;
use app\tool\Random;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Redirect;
use plugin\admin\app\model\Box;
use plugin\admin\app\model\BoxLevel;
use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersDrawLog;
use plugin\admin\app\model\UsersLevel;
use plugin\admin\app\model\UsersCoupon;
use plugin\admin\app\model\UsersLevelLog;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
use support\Db;


use support\Request;
use Tinywan\Jwt\JwtToken;
use Webman\Push\Api;

class BoxController extends BaseController
{

    protected array $noNeedLogin = ['index', 'boxPrize'];


    public function index(Request $request)
    {
        $type = $request->post('type', 1);
        $sort = $request->post('sort', 'asc');
        $rows = Box::where(['type' => $type])
            ->orderBy('id', $sort)
            ->paginate()
            ->items();
        return $this->success('成功', $rows);
    }

    function prize(Request $request)
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
                'name' => (new BoxPrize())->getGradeList()[$grade],
                'chance' => $prizes->sum('chance'),
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
                'name' => (new BoxPrize())->getGradeList()[$grade],
                'chance' => $prizes->sum('chance'),
                'boxPrize' => $prizes,
            ];
        });

        // 将 boxPrize 数据嵌套在 level 对象中
        $level->grade = $prizeData;

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
        $amount = $box->price * $times; #需要支付金额

        $rows = UsersCoupon::where(['user_id' => $request->uid, 'status' => 1])
            ->whereDoesntHave('coupon', function (Builder $query) use ($amount) {
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
        $coupon_id = $request->post('coupon_id');
        $row = Box::find($box_id);
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
        $user_coupon_id = $request->post('user_coupon_id', 0);
        $level_id = $request->post('level_id', 0);
        $box = Box::find($box_id);
        if (empty($box)) {
            return $this->fail('盲盒不存在');
        }
        // 启动事务
        Db::beginTransaction();
        try {
            if (!empty($level_id)) {
                $level = BoxLevel::find($level_id);
                $firstLevel = BoxLevel::getFirstLevel($box_id);
                if (!$level) {
                    return $this->fail('关卡不存在');
                }
                if ($level->box->id != $box_id) {
                    return $this->fail('关卡与盲盒不匹配');
                }
                if (!$firstLevel) {
                    return $this->fail('盲盒不存在关卡');
                }
                if ($level->id != $firstLevel->id) {
                    //非第一关 进行抽奖
                    //找出上一关判断是否有这一关的通关券
                    $getLastLevel = BoxLevel::getLastLevel($box_id, $level->name);
                    $lastPrizes = $getLastLevel->boxPrize()->where(['grade' => 1])->pluck('id');//获取上一关通关券
                    $lastTicket = UsersPrize::where(['user_id' => $request->uid])->whereIn('box_prize_id', $lastPrizes)->get();//获取用户拥有的上一关通关券
                    $lastTicketCount = $lastTicket->count();
                    if ($times > $lastTicketCount) {
                        return $this->fail('通关券不足');
                    }

                    //开始抽奖
                    $draw = UsersDrawLog::create(['times' => $times, 'box_id' => $box_id, 'level_id' => $level_id,'ordersn' => '000000000000']); #创建抽奖记录
                    $winnerPrize = [];
                    $user = User::find($request->uid);
                    for ($i = 0; $i < $times; $i++) {
                        // 从数据库中获取奖品列表，过滤出数量大于 0 的奖品

                        $prizes = BoxPrize::where([['num', '>', 0], 'level_id' => $level_id])->get();
                        // 如果没有可用奖品，返回提示

                        if ($prizes->isEmpty()) {
                            BoxPrize::query()->update(['num' => DB::raw('total')]);
                            $prizes = BoxPrize::where([['num', '>', 0], 'level_id' => $level_id])->get(); // 重新获取奖品列表
                            if ($prizes->isEmpty()) {
                                return $this->fail('没有设置奖池');
                            }
                        }

                        // 计算总概率
                        $totalChance = $prizes->sum('chance');
                        // 生成一个介于 0 和总概率之间的随机数
                        $randomNumber = mt_rand() / mt_getrandmax() * $totalChance;
                        // 累加概率，确定中奖奖品
                        $currentChance = 0.0;
                        foreach ($prizes as $prize) {
                            $currentChance += $prize->chance;
                            if ($randomNumber < $currentChance) {
                                //达人不减数量
                                if ($user->kol == 0){
                                    $prize->decrement('num');
                                }
                                $winnerPrize[] = $prize;
                                // 发放奖品并且记录
                                UsersPrize::create([
                                    'user_id' => $request->uid,
                                    'box_prize_id' => $prize->id,
                                    'mark' => '抽奖获得'
                                ]);
                                UsersPrizeLog::create([
                                    'draw_id' => $draw->id,
                                    'user_id' => $request->uid,
                                    'box_prize_id' => $prize->id,
                                    'mark' => '抽奖获得',
                                ]);
                                //删除用户通关券
                                $ticketToDelete = $lastTicket->shift(); // 移除并返回第一个元素
                                if ($ticketToDelete) {
                                    $ticketToDelete->delete();
                                }
                                break;
                            }
                        }
                    }
                    Db::commit();
                    $api = new Api(
                        'http://127.0.0.1:3232',
                        config('plugin.webman.push.app.app_key'),
                        config('plugin.webman.push.app.app_secret')
                    );
                    // 给客户端推送私有 prize_draw 事件的消息
                    $api->trigger("private-user-{$request->uid}", 'prize_draw', [
                        'winner_prize' => $winnerPrize
                    ]);
                    return $this->json(2, '抽奖成功');
                }
            }

            $amount = $box->price * $times;

            $coupon_amount = Coupon::getCouponAmount($amount, $user_coupon_id);

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

            BoxOrder::create([
                'user_id' => $request->uid,
                'box_id' => $box->id,
                'amount' => $amount,
                'pay_amount' => $pay_amount,
                'coupon_amount' => $coupon_amount,
                'ordersn' => $ordersn,
                'user_coupon_id' => $user_coupon_id,
                'times' => $times,
                'level_id' => $level_id
            ]);
            //先用余额支付 余额不足再用微信支付
            $ret = [];

            $user = User::find($request->uid);
            if ($user->money >= $pay_amount) {
                User::money(-$pay_amount, $request->uid, '购买盲盒');
                $code = 3;
                $msg = '支付成功';

                // 创建一个新的请求对象 直接调用支付
                $notify = new NotifyController();
                $request->set([
                    '_data' => [
                        'get' => ['paytype' => 'balance', 'out_trade_no' => $ordersn, 'attach' => 'box']
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
                $ret = Pay::pay($pay_amount, $ordersn, '购买盲盒', 'box', JwtToken::getUser()->openid);
                $code = 4;
                $msg = '开始微信支付';
            }
            // 提交事务
            Db::commit();
            return $this->json($code, $msg, $ret);
        } catch (\Throwable $e) {
            // 回滚事务
            Db::rollBack();
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
        $list = UsersPrizeLog::with(['boxPrize'])
            ->where(['user_id' => $request->uid])
            ->whereIn('box_prize_id', $prize_ids)
            ->orderBy('id', 'desc')
            ->paginate()
            ->items();
        return $this->success('成功', $list);
    }


}
