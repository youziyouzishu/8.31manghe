<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use plugin\admin\app\model\BoxLevel;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 盲盒关卡 
 */
class BoxLevelController extends Crud
{
    
    /**
     * @var BoxLevel
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new BoxLevel;
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
        $query = $this->doSelect($where, $field, $order)->withSum('boxPrize','chance');
        return $this->doFormat($query, $format, $limit);
    }


    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('box-level/index');
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
            $box_id = $request->post('box_id');
            $name = $request->post('name');
            if ($this->model->where(['box_id' => $box_id,'name'=>$name])->exists()){
                return $this->fail('该关卡已存在');
            }


            return parent::insert($request);
        }
        return view('box-level/insert');
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
            $box_id = $request->post('box_id');
            $level = $request->post('level');
            $row = $this->model->find($request->post('id'));
            if ($row->level != $level && $this->model->where(['box_id' => $box_id,'level'=>$level])->exists()){
                return $this->fail('该关卡已存在');
            }
            return parent::update($request);
        }
        return view('box-level/update');
    }

}
