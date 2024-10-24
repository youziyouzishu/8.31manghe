<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use plugin\admin\app\model\RoomUsers;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 房间用户 
 */
class RoomUsersController extends Crud
{
    
    /**
     * @var RoomUsers
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new RoomUsers;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('room-users/index');
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
        return view('room-users/insert');
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
            return parent::update($request);
        }
        return view('room-users/update');
    }

}
