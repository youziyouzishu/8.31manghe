<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
use support\Request;
use support\Response;
use plugin\admin\app\model\Room;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 房间列表 
 */
class RoomController extends Crud
{
    
    /**
     * @var Room
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Room;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('room/index');
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
        $query = $this->doSelect($where, $field, $order)->with(['user']);
        return $this->doFormat($query, $format, $limit);
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
        return view('room/insert');
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
        return view('room/update');
    }


    /**
     * 取消
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function cancel(Request $request): Response
    {
        $primary_key = $this->model->getKeyName();
        $ids = (array)$request->post($primary_key, []);
        $this->model->whereIn($primary_key, $ids)->each(function ($room) {
            #取消房间
            $room->status = 4;
            $room->save();
            $room->roomPrize()->get()->each(function ($roomPrize)use($room) {
                if ($res = UsersPrize::where(['user_id' => $room->user_id, 'box_prize_id' => $roomPrize->box_prize_id,'price'=>$roomPrize->price])->first()){
                    $res->increment('num',$roomPrize->num);
                }else{
                    UsersPrize::create([
                        'user_id' => $room->user_id,
                        'box_prize_id' => $roomPrize->box_prize_id,
                        'price'=>$roomPrize->price,
                        'num'=>$roomPrize->num,
                        'mark'=>'房间取消返还剩余',
                        'grade' => $roomPrize->grade,
                    ]);
                }
                UsersPrizeLog::create([
                    'user_id' => $room->user_id,
                    'box_prize_id' => $roomPrize->box_prize_id,
                    'mark' => '房间取消返还剩余',
                    'type'=>12,
                    'price'=>$roomPrize->price,
                    'grade' => $roomPrize->grade,
                    'num'=>$roomPrize->num,
                ]);
            });
        });
        return $this->json(0);
    }


}
