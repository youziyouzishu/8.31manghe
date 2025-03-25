<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use plugin\admin\app\model\DreamOrdersPrize;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * DIY中奖信息 
 */
class DreamOrdersPrizeController extends Crud
{
    
    /**
     * @var DreamOrdersPrize
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new DreamOrdersPrize;
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
        $query = $this->doSelect($where, $field, $order)->with(['boxPrize']);
        return $this->doFormat($query, $format, $limit);
    }

    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('dream-orders-prize/index');
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
        return view('dream-orders-prize/insert');
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
        return view('dream-orders-prize/update');
    }

}
