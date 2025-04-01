<?php

namespace plugin\admin\app\controller;

use Carbon\Carbon;
use plugin\admin\app\model\Coupon;
use support\Request;
use support\Response;
use plugin\admin\app\model\UsersCoupon;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;
use Webman\RedisQueue\Client;

/**
 * 用户优惠券 
 */
class UsersCouponController extends Crud
{
    
    /**
     * @var UsersCoupon
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new UsersCoupon;
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
                'name' => '优惠券 '.$item->coupon->id,
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
        return view('users-coupon/index');
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
            $coupon_id = $request->post('coupon_id');
            $coupon = Coupon::find($coupon_id);
            $expired_at = Carbon::now()->addDays($coupon->expired_day);
            $request->set('post',[
                'name'=>$coupon->name,
                'type'=>$coupon->type,
                'amount'=>$coupon->amount,
                'with_amount'=>$coupon->with_amount,
                'expired_at'=>$expired_at->toDateTimeString(),
            ]);

            $data = $this->insertInput($request);
            $id = $this->doInsert($data);
            Client::send('coupon-expire',['event'=>'user_coupon_expire','id'=>$id],$expired_at->timestamp - time());
            return $this->json(0, 'ok', ['id' => $id]);
        }
        return view('users-coupon/insert');
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
        return view('users-coupon/update');
    }

}
