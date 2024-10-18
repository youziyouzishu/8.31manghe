<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use support\Request;
use Tinywan\Jwt\JwtToken;
use Webman\Route;


Route::any('/', [\app\controller\IndexController::class,'index']);

Route::group('/box', function () {
    Route::get('/index',[\app\controller\BoxController::class,'index']);
    Route::get('/prize',[\app\controller\BoxController::class,'prize']);
    Route::get('/canusecoupon',[\app\controller\BoxController::class,'canusecoupon']);
    Route::get('/get_price',[\app\controller\BoxController::class,'get_price']);
    Route::post('/pay',[\app\controller\BoxController::class,'pay']);
    Route::get('/level',[\app\controller\BoxController::class,'level']); //闯关赏查看关卡
    Route::get('/level_prize',[\app\controller\BoxController::class,'level_prize']); //闯关赏关卡详情
});

Route::group('/coupon', function () {
    Route::get('/index',[\app\controller\CouponController::class,'index']);
    Route::post('/receive',[\app\controller\CouponController::class,'receive']);
});


Route::group('/banner', function () {
    Route::get('/index', [\app\controller\BannerController::class,'index']);
});

Route::group('/user', function () {
    Route::post('/login', [\app\controller\UserController::class,'login']);
});

Route::group('/token', function () {
    Route::post('/refreshToken', function (Request $request){
        JwtToken::refreshToken();
        return json(['code' => 0, 'msg' => 'ok']);
    });
});





Route::fallback(function(){
    return json(['code' => 404, 'msg' => '404 not found']);
});

Route::disableDefaultRoute();





