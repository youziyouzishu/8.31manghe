<?php

namespace plugin\admin\app\controller;

use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\model\Box;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
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
     * 查询
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        $query = $this->doSelect($where, $field, $order)->with(['level']);
        return $this->doFormat($query, $format, $limit);
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(Request $request): Response
    {
        return view('box-prize/index');
    }

    public function gift(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $box_prize_ids = (array)$request->post('box_prize_ids');
            $user_id = $request->post('user_id');
            foreach ($box_prize_ids as $box_prize_id) {
                $boxPrize = BoxPrize::find($box_prize_id);
                if ($userPrize = UsersPrize::where(['user_id' => $user_id, 'box_prize_id' => $box_prize_id, 'price' => $boxPrize->price])->first()) {
                    $userPrize->increment('num');
                } else {
                    //给用户发放赏袋
                    UsersPrize::create([
                        'user_id' => $user_id,
                        'box_prize_id' => $box_prize_id,
                        'price' => $boxPrize->price,
                        'mark' => '平台赠送',
                        'num' => 1,
                        'grade' => $boxPrize->grade,
                    ]);
                }
                UsersPrizeLog::create([
                    'user_id' => $user_id,
                    'box_prize_id' => $box_prize_id,
                    'mark' => '平台赠送',
                    'type' => 3,
                    'price' => $boxPrize->price,
                    'grade' => $boxPrize->grade,
                    'num' => 1,
                ]);
            }
            return $this->json(0, 'ok');
        }
        return view('box-prize/gift');
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
            if ($params['chance'] <= 0) {
                return $this->fail('概率必须大于0');
            }
            $box = Box::with(['boxPrize'])->find($params['box_id']);
            if ($box->type != 4 && $params['grade'] == 2 && $params['price'] > $box->price) {
                return $this->fail('市场价不能大于等于盲盒单抽价格');
            }
            $chance = $this->model->where(['box_id' => $params['box_id']])
                ->when($box->type == 4, function (Builder $query) use ($params) {
                    $query->where('level_id', $params['level_id']);
                })
                ->sum('chance');
            if ($params['chance'] + $chance > 100) {
                return $this->fail('概率不能超过100%');
            }
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
            if ($param['chance'] <= 0) {
                return $this->fail('概率必须大于0');
            }
            $row = $this->model->find($param['id']);
            if ($row->box->type == 4) {
                $chance = $this->model->where(['level_id' => $param['level_id'], 'box_id' => $param['box_id'], ['id', '<>', $row->id]])->sum('chance');
            } else {
                if ($param['grade'] == 2 && $param['price'] > $row->box->price) {
                    return $this->fail('市场价不能大于等于盲盒单抽价格');
                }
                $chance = $this->model->where(['box_id' => $param['box_id'], ['id', '<>', $row->id]])->sum('chance');
            }
            if ($row->chance != $param['chance'] && $chance + $param['chance'] > 100) {
                return $this->fail('概率不能超过100%');
            }
            return parent::update($request);
        }
        return view('box-prize/update');
    }

}
