<?php

namespace app\controller;

use Illuminate\Database\Eloquent\Builder;
use plugin\admin\app\model\Room;
use plugin\admin\app\model\RoomPrize;
use plugin\admin\app\model\UsersPrize;
use support\Request;

class RoomController extends BaseController
{
    function create(Request $request)
    {
        $name = $request->post('name');
        $content = $request->post('content');
        $type = $request->post('type');
        $password = $request->post('password');
        $start_at = $request->post('start_at');
        $end_at = $request->post('end_at');
        $num = $request->post('num');
        $prize_ids = $request->post('prize_ids');
        $prize_ids = collect(explode(',', $prize_ids));

        $room = Room::create([
            'user_id' => $request->uid,
            'name' => $name,
            'content' => $content,
            'type' => $type,
            'password' => $password,
            'start_at' => $start_at,
            'end_at' => $end_at,
            'num' => $num,
        ]);
        // 使用 each 方法批量创建关联模型
        $prize_ids->each(function ($prize_id) use ($room) {
            $room->prize()->create(['prize_id' => $prize_id]);
            //软删除用户奖品  取消时恢复
            UsersPrize::where('id', $prize_id)->delete();
        });
        return $this->success();
    }

    function list(Request $request)
    {
        $status = $request->get('status', 1);
        $rows = Room::with([
            'user',
            'roomPrize.userPrize.prize'
        ])
            ->withCount(['prize'])
            ->where('status', $status)
            ->get();
        return $this->success($rows);
    }


}
