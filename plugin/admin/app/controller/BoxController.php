<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use plugin\admin\app\model\Box;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 盲盒管理 
 */
class BoxController extends Crud
{
    
    /**
     * @var Box
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Box;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('box/index');
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
        return view('box/insert');
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
        return view('box/update');
    }

}
