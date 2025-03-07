<?php

namespace plugin\admin\app\controller;

use Carbon\Carbon;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\Option;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersDisburse;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use think\db\Where;
use Throwable;
use Workerman\Worker;

class IndexController
{

    /**
     * 无需登录的方法
     * @var string[]
     */
    protected $noNeedLogin = ['index'];

    /**
     * 不需要鉴权的方法
     * @var string[]
     */
    protected $noNeedAuth = ['dashboard'];

    /**
     * 后台主页
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function index(Request $request): Response
    {
        clearstatcache();
        if (!is_file(base_path('plugin/admin/config/database.php'))) {
            return raw_view('index/install');
        }
        $admin = admin();
        if (!$admin) {
            $name = 'system_config';
            $config = Option::where('name', $name)->value('value');
            $config = json_decode($config, true);
            $title = $config['logo']['title'] ?? 'webman admin';
            $logo = $config['logo']['image'] ?? '/app/admin/admin/images/logo.png';
            return raw_view('account/login',['logo'=>$logo,'title'=>$title]);
        }
        return raw_view('index/index');
    }

    /**
     * 仪表板
     * @param Request $request
     * @return Response
     * @throws Throwable
     */
    public function dashboard(Request $request): Response
    {
        // 使用 Carbon 计算今日注册人数
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $yesterday = Carbon::yesterday();
        // 今日新增用户数
        $today_user_count = User::where('created_at', '>=', $today)->where('created_at', '<', $tomorrow)->count();
        // 7天内新增用户数
        $day7_user_count = User::where('created_at', '>', Carbon::now()->subDays(7))->count();
        // 30天内新增用户数
        $day30_user_count = User::where('created_at', '>', Carbon::now()->subDays(30))->count();
        // 总用户数
        $user_count = User::count();
        // 今日流水
        $today_recharge = UsersDisburse::where('created_at', '>=', $today)->whereHas('user',function ($query){
            $query->where('kol',0);
        })->where('created_at', '<', $tomorrow)->where('scene','<>',2)->sum('amount');
        // 昨日流水
        $yesterday_recharge = UsersDisburse::where('created_at', '>=', $yesterday)->whereHas('user',function ($query){
            $query->where('kol',0);
        })->where('created_at', '<', $today)->where('scene','<>',2)->sum('amount');
        // 总流水
        $recharge = UsersDisburse::whereHas('user',function ($query){
            $query->where('kol',0);
        })->where('scene','<>',2)->sum('amount');



        // 今日支付
        $today_recharge_pay = UsersDisburse::where('created_at', '>=', $today)->whereHas('user',function ($query){
            $query->where('kol',0);
        })->where('created_at', '<', $tomorrow)->where('scene','<>',2)->where('type','<>',2)->sum('amount');
        // 昨日支付
        $yesterday_recharge_pay = UsersDisburse::where('created_at', '>=', $yesterday)->whereHas('user',function ($query){
            $query->where('kol',0);
        })->where('created_at', '<', $today)->where('scene','<>',2)->where('type','<>',2)->sum('amount');
        // 总支付
        $recharge_pay = UsersDisburse::whereHas('user',function ($query){
            $query->where('kol',0);
        })->where('scene','<>',2)->where('type','<>',2)->sum('amount');

        // mysql版本
        $version = Util::db()->select('select VERSION() as version');
        $mysql_version = $version[0]->version ?? 'unknown';

        $day7_detail = [];
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->subDays($i);
            $day7_detail[$date->format('m-d')] = User::where('created_at', '>=', $date->startOfDay())->where('created_at', '<', $date->endOfDay())->count();
        }

        return raw_view('index/dashboard', [
            'today_recharge' => $today_recharge,
            'yesterday_recharge' => $yesterday_recharge,
            'recharge' => $recharge,

            'today_recharge_pay' => $today_recharge_pay,
            'yesterday_recharge_pay' => $yesterday_recharge_pay,
            'recharge_pay' => $recharge_pay,

            'today_user_count' => $today_user_count,
            'day7_user_count' => $day7_user_count,
            'day30_user_count' => $day30_user_count,
            'user_count' => $user_count,
            'php_version' => PHP_VERSION,
            'workerman_version' =>  Worker::VERSION,
            'webman_version' => Util::getPackageVersion('workerman/webman-framework'),
            'admin_version' => config('plugin.admin.app.version'),
            'mysql_version' => $mysql_version,
            'os' => PHP_OS,
            'day7_detail' => array_reverse($day7_detail),
        ]);
    }

}
