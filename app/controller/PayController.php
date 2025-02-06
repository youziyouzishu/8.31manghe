<?php

namespace app\controller;

use app\service\Pay;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use plugin\admin\app\model\BoxOrder;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\DreamOrders;
use plugin\admin\app\model\GoodsOrder;
use support\Request;

class PayController extends BaseController
{
    protected array $noNeedLogin = ['getPayStatus'];
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
            $mark = $row->box->name;
            $pay_amount = $row->pay_amount;
        }elseif ($scene == 'goods'){
            $row = GoodsOrder::where('ordersn', $ordersn)->first();
            $mark = $row->goods->boxPrize->name;
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
        $ret = json_decode($ret);
        $ret = $ret->data;
        if ($ret->trans_stat == 'F'){
            return  $this->fail($ret->resp_desc);
        }
        $qr_code =  $ret->qr_code;
        // 使用构建器创建 QR Code
        $writer = new PngWriter();
        $qrCode = new QrCode(
            data: $qr_code,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 100,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );
        $base64 =  $writer->write($qrCode)->getDataUri();
        return  $this->success('请求支付',['base64'=>$base64,'qr_code'=>$qr_code,'scene'=>$scene,'ordersn'=>$ordersn]);
    }

    #查询是否到账
    function getPayStatus(Request $request)
    {
        $ordersn = $request->post('ordersn');
        $scene  = $request->post('scene');
        if ($scene == 'box'){
            $order = BoxOrder::where(['ordersn' => $ordersn])->first();
            if (!$order){
                return $this->fail('订单不存在');
            }
            if ($order->status == 1){
                return $this->fail('查询未到账');
            }
        }elseif ($scene == 'goods'){
            $order = GoodsOrder::where(['ordersn' => $ordersn])->first();
            if (!$order){
                return $this->fail('订单不存在');
            }
            if ($order->status == 1){
                return $this->fail('查询未到账');
            }
        }elseif ($scene == 'freight'){
            $order = Deliver::where(['ordersn' => $ordersn])->first();
            if (!$order){
                return $this->fail('订单不存在');
            }
            if ($order->status == 0){
                return $this->fail('查询未到账');
            }
        }elseif ($scene == 'dream'){
            $order = DreamOrders::where(['ordersn' => $ordersn])->first();
            if (!$order){
                return $this->fail('订单不存在');
            }
            if ($order->status == 1){
                return $this->fail('查询未到账');
            }
        }else{
            return $this->fail('支付场景错误');
        }
        return $this->success('查询到账');
    }

}
