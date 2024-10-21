<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use plugin\admin\app\model\GoodsClass;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 商品分类 
 */
class GoodsClassController extends Crud
{
    
    /**
     * @var GoodsClass
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new GoodsClass;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('goods-class/index');
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
        return view('goods-class/insert');
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
        return view('goods-class/update');
    }

}
