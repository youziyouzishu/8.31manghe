<?php

namespace plugin\admin\app\controller;

use Carbon\Carbon;
use support\Request;
use support\Response;
use plugin\admin\app\model\Coupon;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;
use Webman\RedisQueue\Client;


/**
 * 优惠券管理 
 */
class CouponController extends Crud
{
    
    /**
     * @var Coupon
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Coupon;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('coupon/index');
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
            $data = $this->insertInput($request);
            $id = $this->doInsert($data);
            return $this->success('ok', ['id' => $id]);
        }
        return view('coupon/insert');
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
        return view('coupon/update');
    }

}
