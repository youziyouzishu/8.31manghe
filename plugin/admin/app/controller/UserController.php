<?php

namespace plugin\admin\app\controller;

use EasyWeChat\MiniApp\Application;
use plugin\admin\app\model\UsersMoneyLog;
use support\Request;
use support\Response;
use plugin\admin\app\model\User;
use support\exception\BusinessException;

/**
 * 用户 
 */
class UserController extends Crud
{
    
    /**
     * @var User
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new User;
    }


    /**
     * 查询
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        $query = $this->doSelect($where, $field, $order)->withSum(['userDisburse'=>function ($query) {
            $query->whereDate('created_at',date('Y-m-d'));
        }],'amount');
        return $this->doFormat($query, $format, $limit);
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('user/index');
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::insert($request);
        }
        return view('user/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException
    */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $user = $this->model->find($request->input('id'));
            $originmoney = $user->money;
            $changemoney = $request->post('money');

            if (!empty($changemoney) && (function_exists('bccomp') ? bccomp($changemoney, $originmoney, 2) !== 0 : (double)$changemoney !== (double)$originmoney)) {

                UsersMoneyLog::create(['user_id' => $user->id, 'money' => $changemoney - $originmoney, 'before' => $originmoney, 'after' => $changemoney, 'memo' => '管理员变更']);
            }
            return parent::update($request);
        }
        return view('user/update');
    }

    function getwxacodeunlimit(Request $request)
    {
        $id = $request->post('id');
        $app = new Application(config('wechat'));
        try {
            $response = $app->getClient()->postJson('/wxa/getwxacodeunlimit', [
                'scene' => 'login',
                'page' => 'pages/index/index',
                'width' => 280,
                'check_path' => !config('app.debug'),

            ]);
            $response = base64_encode($response->getContent());
            return $this->success('生成成功',['base64'=>$response]);
        } catch (\Throwable $e) {
            // 失败
            return $this->fail($e->getMessage());
        }
    }
}
