<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\common\Auth;
use plugin\admin\app\common\Util;
use support\Request;
use support\Response;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 发货列表 
 */
class DeliverController extends Crud
{
    
    /**
     * @var Deliver
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Deliver;
    }

    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        $query = $this->doSelect($where, $field, $order)->where('status','<>',0);
        return $this->doFormat($query, $format, $limit);
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('deliver/index');
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
        return view('deliver/insert');
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
        return view('deliver/update');
    }

    function deliver(Request $request)
    {
        $param = $request->post();
        $row = $this->model->where('id', $param['id'])->first();
        if (!$row) {
            return $this->fail('找不到此数据');
        }
        if ($row->status != 1){
            return $this->fail('订单状态异常');
        }
        $row->status = 2;
        $row->save();
        return $this->success('发货成功');
    }

}
