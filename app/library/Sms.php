<?php

namespace app\library;

use GuzzleHttp\Client;
use plugin\admin\app\common\Util;

class Sms
{
    /**
     * 验证码有效时长
     * @var int
     */
    protected static $expire = 120;

    /**
     * 最大允许检测的次数
     * @var int
     */
    protected static $maxCheckNums = 10;

    /**
     * 获取最后一次手机发送的数据
     *
     * @param   int    $mobile 手机号
     * @param   string $event  事件
     * @return  \plugin\admin\app\model\Sms
     */
    public static function get($mobile, $event = 'default')
    {
        $sms = \plugin\admin\app\model\Sms::where(['mobile' => $mobile, 'event' => $event])
            ->orderByDesc('id')
            ->first();

        return $sms ?: null;
    }

    /**
     * 发送验证码
     *
     * @param   int    $mobile 手机号
     * @param   int    $code   验证码,为空时将自动生成4位数字
     * @param   string $event  事件
     * @return  boolean
     */
    public static function send($mobile, $code = null, $event = 'default')
    {
        $code = is_null($code) ? Util::numeric() : $code;
        $ip = request()->getRealIp();
        $client = new Client();
        // 定义请求的 URL 和数据
        $url = 'http://sms.lifala.com.cn/api/KehuSms/send';
        $data = [
            'appid' => 'apsms7193292067',
            'key' => 'itmqkuN5UfHbQO8n0IGFT9oqJnWhGh7n',
            'mobile' => $mobile,
            'code' => $code,
        ];
        try {
            // 发送 POST 请求
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => $data
            ]);
            // 获取响应体
            $ret = $response->getBody()->getContents();
            $ret = json_decode($ret);

            if ($ret->code != 1) {
                return false;
            }
            \plugin\admin\app\model\Sms::create([
                'event' => $event,
                'mobile' => $mobile,
                'code' => $code,
                'ip' => $ip
            ]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 发送通知
     *
     * @param   mixed  $mobile   手机号,多个以,分隔
     * @param   string $msg      消息内容
     * @param   string $template 消息模板
     * @return  boolean
     */
    public static function notice($mobile, $msg = '', $template = null)
    {
        $params = [
            'mobile'   => $mobile,
            'msg'      => $msg,
            'template' => $template
        ];
        $result = Hook::listen('sms_notice', $params, null, true);
        return (bool)$result;
    }

    /**
     * 校验验证码
     *
     * @param   int    $mobile 手机号
     * @param   int    $code   验证码
     * @param   string $event  事件
     * @return  boolean
     */
    public static function check($mobile, $code, $event = 'default')
    {
        $time = time() - self::$expire;
        $sms = \plugin\admin\app\model\Sms::where(['mobile' => $mobile, 'event' => $event])
            ->orderByDesc('id')
            ->first();
        if ($sms) {
            if ($sms->created_at->timestamp > $time && $sms->times <= self::$maxCheckNums) {
                $correct = $code == $sms->code;
                if (!$correct) {
                    $sms->increment('times');
                    return false;
                } else {
                    return true;
                }
            } else {
                // 过期则清空该手机验证码
                self::flush($mobile, $event);
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 清空指定手机号验证码
     *
     * @param   int    $mobile 手机号
     * @param   string $event  事件
     * @return  boolean
     */
    public static function flush($mobile, $event = 'default')
    {
        \plugin\admin\app\model\Sms::where(['mobile' => $mobile, 'event' => $event])->delete();
        return true;
    }
}