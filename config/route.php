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


Route::any('/', [\app\controller\IndexController::class, 'index']);

Route::group('/box', function () {
    Route::get('/index', [\app\controller\BoxController::class, 'index']);
    Route::get('/boxPrize', [\app\controller\BoxController::class, 'prize']);
    Route::get('/canusecoupon', [\app\controller\BoxController::class, 'canusecoupon']);
    Route::get('/get_price', [\app\controller\BoxController::class, 'get_price']);
    Route::post('/draw', [\app\controller\BoxController::class, 'draw']);
    Route::get('/level', [\app\controller\BoxController::class, 'level']); //闯关赏查看关卡
    Route::get('/level_prize', [\app\controller\BoxController::class, 'level_prize']); //闯关赏关卡详情
    Route::get('/prize_log', [\app\controller\BoxController::class, 'prize_log']); //中奖记录
});

Route::group('/goods', function () {
    Route::get('/class', [\app\controller\GoodsController::class, 'class']);
    Route::get('/index', [\app\controller\GoodsController::class, 'index']);
    Route::get('/detail', [\app\controller\GoodsController::class, 'detail']);
    Route::post('/pay', [\app\controller\GoodsController::class, 'pay']);
});

Route::group('/coupon', function () {
    Route::get('/index', [\app\controller\CouponController::class, 'index']);
    Route::post('/receive', [\app\controller\CouponController::class, 'receive']);
});


Route::group('/banner', function () {
    Route::get('/index', [\app\controller\BannerController::class, 'index']);
});

Route::group('/user', function () {
    Route::post('/login', [\app\controller\UserController::class, 'login']);
    Route::get('/boxPrize', [\app\controller\UserController::class, 'prize']);
    Route::get('/getinfo', [\app\controller\UserController::class, 'getinfo']);
    Route::get('/deliverList', [\app\controller\UserController::class, 'deliverList']);
    Route::get('/getDeliverInfo', [\app\controller\UserController::class, 'getDeliverInfo']);
    Route::post('/confirmReceipt', [\app\controller\UserController::class, 'confirmReceipt']);
});

Route::group('/boxPrize', function () {
    Route::post('/dissolve', [\app\controller\PrizeController::class, 'dissolve']);
    Route::post('/give', [\app\controller\PrizeController::class, 'give']);
    Route::post('/changesafe', [\app\controller\PrizeController::class, 'changesafe']);
    Route::post('/deliver', [\app\controller\PrizeController::class, 'deliver']);
    Route::post('/getPrizesFreight', [\app\controller\PrizeController::class, 'getPrizesFreight']);
});

Route::group('/address', function () {
    Route::post('/add', [\app\controller\AddressController::class, 'add']);
    Route::post('/setDefault', [\app\controller\AddressController::class, 'setDefault']);
    Route::get('/getDefault', [\app\controller\AddressController::class, 'getDefault']);
    Route::get('/get', [\app\controller\AddressController::class, 'get']);
    Route::post('/edit', [\app\controller\AddressController::class, 'edit']);
    Route::post('/delete', [\app\controller\AddressController::class, 'delete']);
    Route::get('/getList', [\app\controller\AddressController::class, 'getList']);
});

Route::group('/token', function () {
    Route::post('/refreshToken', function (Request $request) {
        $newToken = JwtToken::refreshToken();
        return json(['code' => 0, 'msg' => '成功', 'data' => $newToken]);
    });
});


Route::fallback(function () {
    return json(['code' => 404, 'msg' => '404 not found']);
});

Route::disableDefaultRoute();





