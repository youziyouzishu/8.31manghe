<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\model\UsersMoneyLog;
use support\Request;
use support\Response;
use plugin\admin\app\model\User;
use plugin\admin\app\controller\Crud;
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

}
