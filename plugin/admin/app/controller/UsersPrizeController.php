<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\model\BoxPrize;
use plugin\admin\app\model\UsersPrizeLog;
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
        $query = $this->doSelect($where, $field, $order)->with(['boxPrize.box','user']);
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
            $param = $request->post();
            $boxPrize = BoxPrize::find($param['box_prize_id']);

            if ($userPrize = UsersPrize::where(['user_id' => $param['user_id'], 'box_prize_id' => $param['box_prize_id'], 'price' => $boxPrize->price])->first()) {
                $userPrize->increment('num',$param['num']);
                $id = $userPrize->id;
            } else {
                //给用户发放赏袋
                $userPrize = UsersPrize::create([
                    'user_id' => $param['user_id'],
                    'box_prize_id' => $param['box_prize_id'],
                    'price' => $boxPrize->price,
                    'mark' => '平台赠送',
                    'num' => $param['num'],
                    'grade' => $boxPrize->grade,
                ]);
                $id = $userPrize->id;
            }

            UsersPrizeLog::create([
                'user_id'=>$param['user_id'],
                'box_prize_id'=>$param['box_prize_id'],
                'mark'=>'平台赠送',
                'type'=>3,
                'price'=>$boxPrize->price,
                'grade'=>$boxPrize->grade,
                'num' => $param['num'],
            ]);
            return $this->json(0, 'ok', ['id' => $id]);
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
