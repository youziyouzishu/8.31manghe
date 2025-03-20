<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\model\BoxGrade;
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
        $query = $this->doSelect($where, $field, $order)
            ->withCount('boxPrize')
            ->withSum(['boxPrize'], 'chance')
            ->withSum(['boxPrize'], 'price');
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
        foreach ($items as $item) {
            $item['box_original_prize'] = empty($item['box_prize_count']) || empty($item['box_prize_sum_price']) ? 0 : round($item['box_prize_sum_price'] / $item['box_prize_count'], 2);
        }
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
            $params = $request->post();
            if (!empty($params['rate']) && ($params['rate'] < 0 || $params['rate'] > 1)) {
                return $this->fail('毛利率必须大于0且小于1');
            }
            $data = $this->insertInput($request);
            $id = $this->doInsert($data);

            //用户
            BoxGrade::create([
                'box_id' => $id,
                'grade' => 1,
                'type'=>1,
            ]);
            BoxGrade::create([
                'box_id' => $id,
                'grade' => 2,
                'type'=>1,
            ]);
            BoxGrade::create([
                'box_id' => $id,
                'grade' => 3,
                'type'=>1,
            ]);
            BoxGrade::create([
                'box_id' => $id,
                'grade' => 4,
                'type'=>1,
            ]);
            BoxGrade::create([
                'box_id' => $id,
                'grade' => 5,
                'type'=>1,
            ]);

            //KOL
            BoxGrade::create([
                'box_id' => $id,
                'grade' => 1,
                'type'=>2,
            ]);
            BoxGrade::create([
                'box_id' => $id,
                'grade' => 2,
                'type'=>2,
            ]);
            BoxGrade::create([
                'box_id' => $id,
                'grade' => 3,
                'type'=>2,
            ]);
            BoxGrade::create([
                'box_id' => $id,
                'grade' => 4,
                'type'=>2,
            ]);
            BoxGrade::create([
                'box_id' => $id,
                'grade' => 5,
                'type'=>2,
            ]);
            return $this->json(0, 'ok', ['id' => $id]);
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
            $params = $request->post();
            if (!empty($params['rate']) && ($params['rate'] < 0 || $params['rate'] > 1)) {
                return $this->fail('毛利率必须大于0且小于1');
            }

            return parent::update($request);
        }
        return view('box/update');
    }

    /**
     * 删除
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function delete(Request $request): Response
    {
        $ids = $this->deleteInput($request);
        $this->doDelete($ids);
        return $this->json(0);
    }

    /**
     * 修改毛利率
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function rate(Request $request): Response
    {
        $ids = $request->post('id');
        $rate = $request->post('rate');
        $kol_rate = $request->post('kol_rate');
        $data = [];
        if (!empty($rate)) {
            if ($rate < 0 || $rate > 1) {
                return $this->fail('毛利率必须大于0且小于1');
            }
            $data['rate'] = $rate;
        }
        if (!empty($kol_rate)) {
            if ($kol_rate < 0 || $kol_rate > 1) {
                return $this->fail('KOL毛利率必须大于0且小于1');
            }
            $data['kol_rate'] = $kol_rate;
        }
        $this->model->whereIn('id', $ids)->update($data);
        return $this->json(0);
    }

}
