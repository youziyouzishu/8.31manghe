<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use plugin\admin\app\model\Room;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 房间列表 
 */
class RoomController extends Crud
{
    
    /**
     * @var Room
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Room;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('room/index');
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
        return view('room/insert');
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
        return view('room/update');
    }

}
