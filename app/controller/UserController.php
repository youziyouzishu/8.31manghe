<?php

namespace app\controller;

use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use plugin\admin\app\model\User;
use support\Request;
use EasyWeChat\MiniApp\Application;
use Tinywan\Jwt\JwtToken;

class UserController extends  BaseController
{
    protected array $noNeedLogin = ['login'];

    public function index(Request $request)
    {
        return response(__CLASS__);
    }

    function login(Request $request)
    {

        try {
            $app = new Application(config('wechat'));
            $res =  $app->getUtils()->codeToSession((string) request()->post('code'));
            $openid = $res['openid'];
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }

        $row = User::where('openid',$openid)->first();
        if (!$row){
            $data = [
                'nickname'=>'用户'.mt_rand(10000,99999),
                'avatar'=>'/app/admin/upload/files/20241014/670c7690a977.jpg',
                'openid'=>$openid,
                'join_time'=>  date('Y-m-d H:i:s'),
                'join_ip' => $request->getRealIp(),
                'last_time'=> date('Y-m-d H:i:s'),
                'last_ip'=> $request->getRealIp()
            ];
            $row = User::forceCreate($data);
        }else{
            $row->last_time = date('Y-m-d H:i:s');
            $row->last_ip = $request->getRealIp();
            $row->save();
        }
        $token = JwtToken::generateToken($row->toArray());
        return $this->success('成功',$token);
    }


}
