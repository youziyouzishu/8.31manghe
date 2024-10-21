<?php

namespace app\controller;

use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersPrize;
use support\Request;
use EasyWeChat\MiniApp\Application;
use Tinywan\Jwt\JwtToken;

class UserController extends  BaseController
{
    protected array $noNeedLogin = ['login'];

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
            // 获取下一个自增ID
            $nextId = User::max('id') + 1;
            $data = [
                'nickname'=>'昵称'.$nextId,
                'avatar'=>'/app/admin/upload/files/20241014/670c7690a977.jpg',
                'openid'=>$openid,
                'join_time'=>  date('Y-m-d H:i:s'),
                'join_ip' => $request->getRealIp(),
                'last_time'=> date('Y-m-d H:i:s'),
                'last_ip'=> $request->getRealIp()
            ];
            $row = User::create($data);
        }else{
            $row->last_time = date('Y-m-d H:i:s');
            $row->last_ip = $request->getRealIp();
            $row->save();
        }
        $row->client = JwtToken::TOKEN_CLIENT_MOBILE;
        $token = JwtToken::generateToken($row->toArray());
        return $this->success('成功',$token);
    }

    function getinfo(Request $request)
    {
        $row = User::find($request->uid);
        return $this->success('成功',$row);
    }

    function prize(Request $request)
    {
        $safe = $request->get('safe', 0);

        $rows = UsersPrize::with(['boxPrize'])
            ->where(['user_id' => $request->uid, 'safe' => $safe])
            ->paginate()
            ->items();
        return $this->success('成功', $rows);
    }

    function deliverList(Request $request)
    {
        $status = $request->get('status', 0);

        $rows = Deliver::with(['detail'])
            ->where(['user_id' => $request->uid])
            ->when(!empty($status),function (Builder $query)use($status){
                if ($status == 1){
                    $query->where('status',2);
                }
                if ($status == 2){
                    $query->where('status',3);
                }
                if ($status == 3){
                    $query->where('status',4);
                }
            })
            ->paginate()
            ->items();
        return $this->success('成功', $rows);
    }

    function getDeliverInfo(Request $request)
    {
        $deliver_id = $request->get('deliver_id');
        $row = Deliver::with(['detail','address'])
            ->where(['user_id' => $request->uid, 'id' => $deliver_id])
            ->first();
        return $this->success('成功', $row);
    }

    function confirmReceipt(Request $request)
    {
        $deliver_id = $request->post('deliver_id');
        $row = Deliver::where(['user_id' => $request->uid, 'id' => $deliver_id])
            ->first();
        $row->status = 3;
        $row->save();
        return $this->success();
    }


}
