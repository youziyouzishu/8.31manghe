<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use plugin\admin\app\model\Goods;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 商品管理 
 */
class GoodsController extends Crud
{
    
    /**
     * @var Goods
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Goods;
    }

    /**
     * 格式化下拉列表
     * @param $items
     * @return Response
     */
    protected function formatSelect($items): Response
    {
        $formatted_items = [];
        $primary_key = $this->model->getKeyName();
        foreach ($items as $item) {
            $formatted_items[] = [
                'name' => $item->boxPrize->name,
                'value' => $item->$primary_key
            ];
        }
        return  $this->json(0, 'ok', $formatted_items);
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('goods/index');
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
        return view('goods/insert');
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
        return view('goods/update');
    }

}
