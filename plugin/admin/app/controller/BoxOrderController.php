<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 盲盒订单 
 */
class BoxOrderController extends Crud
{
    
    /**
     * @var BoxOrder
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new BoxOrder;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('box-order/index');
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
        return view('box-order/insert');
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
        return view('box-order/update');
    }

}
