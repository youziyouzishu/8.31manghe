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
     * 查询
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        $query = $this->doSelect($where, $field, $order)->withCount('boxPrize')->withSum(['boxPrize'], 'total');
        return $this->doFormat($query, $format, $limit);
    }

    /**
     * 执行真正查询，并返回格式化数据
     * @param $query
     * @param $format
     * @param $limit
     * @return Response
     */
    protected function doFormat($query, $format, $limit): Response
    {
        $methods = [
            'select' => 'formatSelect',
            'tree' => 'formatTree',
            'table_tree' => 'formatTableTree',
            'normal' => 'formatNormal',
        ];
        $paginator = $query->paginate($limit);
        $total = $paginator->total();
        $items = $paginator->items();
        collect($items)->each(function ($item) {
            //box_prize_sum_total
            $item->box_prize_sum_price = $item->boxPrize->sum(function ($prize) {
                return $prize->price * $prize->total;
            });
            if ($item->box_prize_sum_total == 0 || $item->box_prize_sum_price == 0){
                $item->box_original_prize = 0;
            }else{
                $item->box_original_prize = round(  $item->box_prize_sum_price / $item->box_prize_sum_total,2);
            }
        });
        if (method_exists($this, "afterQuery")) {
            $items = call_user_func([$this, "afterQuery"], $items);
        }
        $format_function = $methods[$format] ?? 'formatNormal';
        return call_user_func([$this, $format_function], $items, $total);
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
