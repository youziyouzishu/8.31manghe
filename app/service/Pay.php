<?php

namespace app\service;

use Exception;
use GuzzleHttp\Client;
use plugin\admin\app\common\Util;
use Yansongda\Artful\Rocket;
use Yansongda\Supports\Collection;

class Pay
{
    /**
     * 支付
     * @param  $pay_amount
     * @param  $order_no
     * @param $mark
     * @param $attach
     * @param string $openid
     */
    public static function pay($pay_amount, $order_no, $mark, $attach, string $openid = '')
    {
//        $config = config('payment');
//        return \Yansongda\Pay\Pay::wechat($config)->mini([
//            'out_trade_no' => $order_no,
//            'description' => $mark,
//            'amount' => [
//                'total' => function_exists('bcmul') ? (int)bcmul($pay_amount, 100, 2) : $pay_amount * 100,
//                'currency' => 'CNY',
//            ],
//            'payer' => [
//                'openid' => $openid,
//            ],
//            'attach' => $attach
//        ]);
        $client = new Client();
        $url = 'https://api.huifu.com/v2/trade/payment/jspay';
        $sys_id = '6666000159541570';
        $product_id = 'PAYUN';
        $data = [
            'req_date'=>date('Ymd'),
            'req_seq_id'=>$order_no,
            'huifu_id'=>'6666000159541570',
            'goods_desc'=>$mark,
            'trade_type'=>'A_NATIVE',
            'trans_amt'=>$pay_amount,
            'notify_url'=> 'https://0831manghe.62.hzgqapp.com/notify/alipay',
            'remark'=>$attach
        ];

        ksort($data);
        $signdata = json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $signature= '';
        $key = "-----BEGIN PRIVATE KEY-----\n".'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCLBNo6K6ktvnguReo8l9U714ULVgi3m9pcIygnwad7HEkt/0aoWRD/0+x5NEA2aui/5sYUrwnm0Ug06TEoZRot0g9TWxbIk9r+H4flTGS3ELJ0TODYO1JnyMfxTGs5uUO4mcHMo2nQ3AbpymgvwoFlFj8qHdDe+jc0SodzH/B/be0IzrEx6/y5T4BowsPAOP5BvNXDI1yIxF4Cibx4NH68qMKmHWlNBaY8ykgVrJdSuJsONLVn4dDIxhDFhTxCU3NxYmNdry69Kyh3VwXu4++b79RfOpFOnW54LGqVFXrWRW489l9wnQnVTg8NxFQm63j7M0aH/ycwKElRRvY5UyCbAgMBAAECggEAGKYPm2jKYlX3MR0sXvn80NNxv4TNnuB/Yv7Iy+PGMkndn843VPoVzYZh00u0ZvGluwDL9jbi5o4M43TFRohGkAFCvmDUNVQh7BTjH4DgCqVnPr+yziQWhFhROzN1f5Kds0lv1zzSMjoQ77r3piAymR3MrUcEFcT/rMHaKktrdOxhOzyv9wb5ZMy5gEpV0jXASHa8UbIZMhCOturkSEJhUiBHlt+LEp1mNSLX6V3VgY2+c5smTguiwWwlvJ4wasrMoVQ5I2MTdchYyooQW8iW2tqOcPCh7gmYYjWGhSALC2ETxlzkTHLTxeia6PyABMV3sbU6aW+PoTWK2Omhk9AmIQKBgQDWhbtmK5T0QoC83O7rDz++ZKnnck1LRv9abDVYc6BTXSse3KD0/lLzaGKfUIDILYQ+X6JShVrd1w8lwoOTiflLVwpjILmRZ77LgN8aBmPp+1Nib2ie0pM7BKLLXSl1ZBPS99eiWVbb7TA+r6xmoRLZxydtSZW7VT41F96fEHY6zQKBgQCl5eh7f8xRFUvC0aASOWYdn2iogxoJDsubLARNvcGx1sZ7ARyIWiTNCOdT5vjl+6Bm3+LNItCaWzkYd4IIZq21+sbOdOnU1TUNf+D9UOfzOMUhOfPmWkyBRODCrIqj/mVxKLq/WkSABaEwTKhtzaJqWNycjkv25dDf0xXNNr+ZBwKBgGWZmuLN21NAN/a68JD1fOwwguOyF/eCHah2vWEyCgnRg32vYrAFz8Wmd6campO0MwDTjG5m6F1O4MaydFypKR/MjofaVOkP6KY7V+7cfe+wb5KcT0GBW+fEz1FfwyXtCxKM/VzP0TqCAKp/yzgkK2hnUT4Kbtb1jWvZ7T8KreQ1AoGAXKFXwwVjnu6GI9yKRUK5atbkSBsTwXT6aMUWDhZi/ZqProS7WsMCg11yVN3Fohxyvp5J9AJ5eYZwBeJMv8YdPlws/4A2Q/lcKxJ5HNg8+wh6wYerULaguxkrameO7eyQ1bNJOqj7UIRUMTTMYqsMAddmLyAg+FXv39nr9W6ZYqcCgYEArYYKfzFpyi5cdjUYdNoV+4QDOexFU4QsYUNZUjYF+9RxALqxcgcpiUfzOM3THxgL0yY+ZUZj2nINgi4sXZ64GZVt9wTGBINu/F+9cG1QGHqCaLxWKNTPTocHeYa7lCJ+BPzv+a+dlNZzAeRHztuNAjJjD3Uk6kRuEExRtC5oBXU='."\n-----END PRIVATE KEY-----";
        openssl_sign($signdata, $signature, $key, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($signature);

        $postdata = [
            'sys_id' => $sys_id,
            'product_id' => $product_id,
            'data' => $data,
            'sign'=>$sign
        ];
        dump($postdata);
        $result = $client->post($url, [
            'json' => $postdata,
        ]);
        return $result->getBody()->getContents();
    }

    #退款
    public static function refund($pay_type, $pay_amount, $order_no, $refund_order_no, $reason)
    {
        $config = config('payment');
        return match ($pay_type) {
            1 => \Yansongda\Pay\Pay::wechat($config)->refund([
                'out_trade_no' => $order_no,
                'out_refund_no' => $refund_order_no,
                'amount' => [
                    'refund' => (int)bcmul($pay_amount, 100, 2),
                    'total' => (int)bcmul($pay_amount, 100, 2),
                    'currency' => 'CNY',
                ],
                'reason' => $reason
            ]),
            default => throw new Exception('支付类型错误'),
        };
    }
}