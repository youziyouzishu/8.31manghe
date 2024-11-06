<?php

namespace app\controller;

use app\library\Sms;
use Carbon\Carbon;
use plugin\admin\app\model\User;
use Respect\Validation\Validator;
use support\Request;
use support\Response;

class SmsController extends BaseController
{
    protected array $noNeedLogin = ['send'];
    /**
     * 发送验证码
     *
     * @ApiMethod (POST)
     * @param string $mobile 手机号
     * @param string $event 事件名称
     */
    public function send(Request $request):Response
    {
        $mobile = $request->post("mobile");
        $event = $request->post("event");
        $event = $event ?: 'register';

        if (!$mobile || !Validator::mobile()->validate($mobile)) {
            return $this->fail('手机号不正确');
        }
        $last = Sms::get($mobile, $event);
        if ($last && time() - $last->created_at->timestamp < 60) {
            return $this->fail('发送频繁');
        }
        // 获取当前小时的开始和结束时间
        $startTime = Carbon::now()->startOfHour();
        $endTime = Carbon::now()->endOfHour();
        $ipSendTotal = \plugin\admin\app\model\Sms::where(['ip' => $request->getRealIp()])->whereBetween('created_at', [$startTime, $endTime])->count();
        if ($ipSendTotal >= 5) {
            return $this->fail('发送频繁');
        }
        if ($event) {
            $userinfo = User::where(['mobile'=>$mobile])->first();
            if ($event == 'register' && $userinfo) {
                //已被注册
                return $this->fail('已被注册');
            } elseif ($event == 'changemobile' && $userinfo) {
                //被占用
                return $this->fail('已被占用');
            } elseif (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo) {
                //未注册
                return $this->fail('未注册');
            }
        }

        $ret = Sms::send($mobile, null, $event);
        if ($ret) {
            return $this->success('发送成功');
        } else {
            return $this->fail('发送失败，请检查短信配置是否正确');
        }
    }
}