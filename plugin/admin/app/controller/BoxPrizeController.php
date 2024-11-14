<?php

namespace plugin\admin\app\controller;

use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\model\Box;
use support\Request;
use support\Response;
use plugin\admin\app\model\BoxPrize;
use support\exception\BusinessException;

/**
 * 盲盒详情
 */
class BoxPrizeController extends Crud
{

    /**
     * @var BoxPrize
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new BoxPrize;
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(Request $request): Response
    {
        return view('box-prize/index');
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
            if ($params['chance'] <= 0){
                return $this->fail('概率必须大于0');
            }
            $box = Box::with(['boxPrize'])->find($params['box_id']);
            $row = $this->model->where(['box_id' => $params['box_id']])
                ->when($box->type == 4, function (Builder $query) use ($params) {
                    $query->where('level_id', $params['level_id']);
                })
                ->sum('chance');

            if ($params['chance'] + $row > 100) {
                return $this->fail('概率不能超过100%');
            }
            $request->set('post',['num'=>$request->post('total')]);

            return parent::insert($request);
        }
        return view('box-prize/insert');
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
            $param = $request->post();
            if ($param['chance'] <= 0){
                return $this->fail('概率必须大于0');
            }
            $row = $this->model->find($param['id']);
            if ($row->box->type == 4) {
                $chance = $this->model->where(['level_id' => $param['level_id'],'box_id' => $param['box_id'],['id','<>',$row->id]])->sum('chance');
                if ($row->chance != $param['chance'] && $chance + $param['chance'] > 100) {
                    return $this->fail('概率不能超过100%');
                }
            } else {
                $chance = $this->model->where(['box_id' => $param['box_id'],['id','<>',$row->id]])->sum('chance');
                if ($row->chance != $param['chance'] && $chance + $param['chance'] > 100) {
                    return $this->fail('概率不能超过100%');
                }
            }
            return parent::update($request);
        }
        return view('box-prize/update');
    }

}
