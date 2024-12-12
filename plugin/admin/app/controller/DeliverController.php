<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\common\Auth;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\DeliverDetail;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
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
        $query = $this->doSelect($where, $field, $order)->where('status','<>',0)->with(['user','address']);
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

    function cancel(Request $request)
    {
        $param = $request->post();
        $row = $this->model->where('id', $param['id'])->first();
        if (!$row) {
            return $this->fail('找不到此数据');
        }
        if ($row->status != 1){
            return $this->fail('订单状态异常');
        }
        $row->status = 4;
        $row->mark = $param['mark'];
        $row->save();
        $data = [];
        $row->detail->each(function (DeliverDetail $item)use($row,&$data){
            UsersPrizeLog::create([
                'user_id'=>$row->user_id,
                'box_prize_id'=>1,
                'mark'=>'取消发货返还奖品',
                'type'=>9,
                'price'=>$item->price,
                'grade'=>$item->boxPrize->grade
            ]);
            $data[] = [
                'user_id'=>$row->user_id,
                'box_prize_id'=>$item->box_prize_id,
                'num'=>$item->num,
                'mark'=>'取消发货返还奖品',
                'price'=>$item->price,
            ];
        });
        UsersPrize::insert($data);
        return $this->success('取消成功');
    }

}
