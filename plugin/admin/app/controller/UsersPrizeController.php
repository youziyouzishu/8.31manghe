<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\model\BoxPrize;
use support\Request;
use support\Response;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 用户奖品 
 */
class UsersPrizeController extends Crud
{
    
    /**
     * @var UsersPrize
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new UsersPrize;
    }

    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        $query = $this->doSelect($where, $field, $order)->with(['boxPrize.box']);
        return $this->doFormat($query, $format, $limit);
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('users-prize/index');
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
            $boxPrize = BoxPrize::find($request->post('box_prize_id'));
            $request->set('post',['price'=>$boxPrize->price]);
            return parent::insert($request);
        }
        return view('users-prize/insert');
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
        return view('users-prize/update');
    }

}
