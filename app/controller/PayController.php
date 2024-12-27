<?php

namespace app\controller;

use app\service\Pay;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\DreamOrders;
use plugin\admin\app\model\GoodsOrder;
use support\Request;

class PayController extends BaseController
{

    function pay(Request $request)
    {
        $scene = $request->post('scene'); # dream  box  goods  freight
        $ordersn = $request->post('ordersn');
        $pay_type = $request->post('pay_type'); #1=支付宝,2=云闪付
        if ($scene == 'dream'){
            $row = DreamOrders::where('ordersn', $ordersn)->first();
            $mark = 'DIY抽奖';
            $pay_amount = $row->pay_amount;
        }elseif ($scene == 'box'){
            $row = BoxOrder::where('ordersn', $ordersn)->first();
            $mark = '盲盒抽奖';
            $pay_amount = $row->pay_amount;
        }elseif ($scene == 'goods'){
            $row = GoodsOrder::where('ordersn', $ordersn)->first();
            $mark = '购买商品';
            $pay_amount = $row->pay_amount;
        }elseif ($scene == 'freight'){
            $row = Deliver::where('ordersn', $ordersn)->first();
            $mark = '支付运费';
            $pay_amount = $row->pay_amount;
        }else{
            return $this->fail('支付场景错误');
        }
        if (!$row){
            return $this->fail('订单不存在');
        }
        if (!in_array($pay_type,[1,2])){
            return $this->fail('支付方式错误');
        }
        $ret = Pay::pay($pay_type,$pay_amount,$ordersn,$mark,$scene);
        return  $this->success('请求支付',$ret);
    }

}
