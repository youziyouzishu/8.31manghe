<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use plugin\admin\app\model\Effect;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 特效配置 
 */
class EffectController extends Crud
{
    
    /**
     * @var Effect
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Effect;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('effect/index');
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
        return view('effect/insert');
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
        return view('effect/update');
    }

}
