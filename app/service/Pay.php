<?php

namespace app\service;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class Pay
{
    /**
     * 支付
     * @param $pay_type 1=支付宝 2=云闪付
     * @param  $pay_amount
     * @param  $order_no
     * @param $mark
     * @param $attach
     * @return string
     * @throws GuzzleException
     */
    public static function pay($pay_type,$pay_amount, $order_no, $mark, $attach)
    {
        if (!in_array($pay_type,[1,2])){
            throw new Exception('支付类型错误');
        }

        $client = new Client();
        $url = 'https://api.huifu.com/v2/trade/payment/jspay';
        $sys_id = '6666000159541570';
        $product_id = 'PAYUN';
        $notify_url = $pay_type == 1 ? 'https://xinganya.cn/notify/alipay': 'https://xinganya.cn/notify/unipay';
        $data = [
            'req_date'=>date('Ymd'),
            'req_seq_id'=>$order_no,
            'huifu_id'=>'6666000159541570',
            'goods_desc'=>$mark,
            'trade_type'=>$pay_type == 1 ?'A_NATIVE' : 'U_NATIVE',
            'trans_amt'=>$pay_amount,
            'notify_url'=> $notify_url,
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
        $result = $client->post($url, [
            'json' => $postdata,
        ]);
        return $result->getBody()->getContents();
    }

    #退款
    public static function refund($pay_amount, $order_no)
    {
        $client = new Client();
        $url = 'https://api.huifu.com/v2/trade/payment/scanpay/refund';
        $sys_id = '6666000159541570';
        $product_id = 'PAYUN';
        $data = [
            'req_date'=>date('Ymd'),
            'req_seq_id'=>$order_no,
            'huifu_id'=>'6666000159541570',
            'ord_amt'=>$pay_amount,
            'org_req_date'=>date('Ymd')
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
        $result = $client->post($url, [
            'json' => $postdata,
        ]);
        return $result->getBody()->getContents();
    }
}