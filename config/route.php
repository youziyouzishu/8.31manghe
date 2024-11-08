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
Route::any('/log', [\app\controller\IndexController::class, 'log']);

Route::group('/notify', function () {
    Route::any('/wechat', [\app\controller\NotifyController::class, 'wechat']);
//    Route::any('/balance', [\app\controller\NotifyController::class, 'balance']);

});

Route::group('/box', function () {
    Route::post('/index', [\app\controller\BoxController::class, 'index']);
    Route::post('/boxPrize', [\app\controller\BoxController::class, 'boxPrize']);
    Route::post('/canuseCoupon', [\app\controller\BoxController::class, 'canuseCoupon']);
    Route::post('/getPrice', [\app\controller\BoxController::class, 'getPrice']);
    Route::post('/draw', [\app\controller\BoxController::class, 'draw']);
    Route::post('/level', [\app\controller\BoxController::class, 'level']); //闯关赏查看关卡
    Route::post('/levelPrize', [\app\controller\BoxController::class, 'levelPrize']); //闯关赏关卡详情
    Route::post('/prizeLog', [\app\controller\BoxController::class, 'prizeLog']); //中奖记录
    Route::post('/getDrawLog', [\app\controller\BoxController::class, 'getDrawLog']); //中奖记录
});

Route::group('/sms', function () {
    Route::post('/send', [\app\controller\SmsController::class, 'send']);

});

Route::group('/upload', function () {
    Route::post('/file', [\app\controller\UploadController::class, 'file']);
});

Route::group('/goods', function () {
    Route::post('/class', [\app\controller\GoodsController::class, 'class']);
    Route::post('/index', [\app\controller\GoodsController::class, 'index']);
    Route::post('/detail', [\app\controller\GoodsController::class, 'detail']);
    Route::post('/pay', [\app\controller\GoodsController::class, 'pay']);
});

Route::group('/userCoupon', function () {
    Route::post('/index', [\app\controller\CouponController::class, 'index']);
    Route::post('/receive', [\app\controller\CouponController::class, 'receive']);
});


Route::group('/banner', function () {
    Route::post('/index', [\app\controller\BannerController::class, 'index']);
});

Route::group('/config', function () {
    Route::post('/getAgreement', [\app\controller\ConfigController::class, 'getAgreement']);
});

Route::group('/user', function () {
    Route::post('/login', [\app\controller\UserController::class, 'login']);
    Route::post('/boxPrize', [\app\controller\UserController::class, 'boxPrize']);
    Route::post('/getinfo', [\app\controller\UserController::class, 'getinfo']);
    Route::post('/deliverList', [\app\controller\UserController::class, 'deliverList']);
    Route::post('/getDeliverInfo', [\app\controller\UserController::class, 'getDeliverInfo']);
    Route::post('/confirmReceipt', [\app\controller\UserController::class, 'confirmReceipt']);
    Route::post('/editAvatar', [\app\controller\UserController::class, 'editAvatar']);
    Route::post('/editNickname', [\app\controller\UserController::class, 'editNickname']);
    Route::post('/giveLog', [\app\controller\UserController::class, 'giveLog']);
    Route::post('/getMoneyLog', [\app\controller\UserController::class, 'getMoneyLog']);
    Route::post('/receiveLog', [\app\controller\UserController::class, 'receiveLog']);
    Route::post('/consumeLog', [\app\controller\UserController::class, 'consumeLog']);
    Route::post('/consumeDetail', [\app\controller\UserController::class, 'consumeDetail']);
    Route::post('/couponList', [\app\controller\UserController::class, 'couponList']);
    Route::post('/getUserInfoById', [\app\controller\UserController::class, 'getUserInfoById']);
    Route::post('/receive', [\app\controller\UserController::class, 'receive']);
    Route::post('/changeMobile', [\app\controller\UserController::class, 'changeMobile']);
});

Route::group('/prize', function () {
    Route::post('/dissolve', [\app\controller\PrizeController::class, 'dissolve']);
    Route::post('/give', [\app\controller\PrizeController::class, 'give']);
    Route::post('/changesafe', [\app\controller\PrizeController::class, 'changesafe']);
    Route::post('/deliver', [\app\controller\PrizeController::class, 'deliver']);
    Route::post('/getPrizesFreight', [\app\controller\PrizeController::class, 'getPrizesFreight']);
});

Route::group('/address', function () {
    Route::post('/add', [\app\controller\AddressController::class, 'add']);
    Route::post('/setDefault', [\app\controller\AddressController::class, 'setDefault']);
    Route::post('/getDefault', [\app\controller\AddressController::class, 'getDefault']);
    Route::post('/get', [\app\controller\AddressController::class, 'get']);
    Route::post('/edit', [\app\controller\AddressController::class, 'edit']);
    Route::post('/delete', [\app\controller\AddressController::class, 'delete']);
    Route::post('/getList', [\app\controller\AddressController::class, 'getList']);
});

Route::group('/room', function () {
    Route::post('/create', [\app\controller\RoomController::class, 'create']);
    Route::post('/list', [\app\controller\RoomController::class, 'list']);
    Route::post('/roomDetail', [\app\controller\RoomController::class, 'roomDetail']);
    Route::post('/roomUsers', [\app\controller\RoomController::class, 'roomUsers']);
    Route::post('/joinRoom', [\app\controller\RoomController::class, 'joinRoom']);
    Route::post('/winList', [\app\controller\RoomController::class, 'winList']);
    Route::post('/createList', [\app\controller\RoomController::class, 'createList']);
    Route::post('/cancel', [\app\controller\RoomController::class, 'cancel']);
    Route::post('/edit', [\app\controller\RoomController::class, 'edit']);
});

Route::group('/dream', function () {
    Route::post('/getPrice', [\app\controller\DreamController::class, 'getPrice']);
    Route::post('/getUserOrders', [\app\controller\DreamController::class, 'getUserOrders']);
    Route::post('/index', [\app\controller\DreamController::class, 'index']);
    Route::post('/draw', [\app\controller\DreamController::class, 'draw']);
    Route::post('/getOrders', [\app\controller\DreamController::class, 'getOrders']);
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





