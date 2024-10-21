<?php

namespace app\controller;

use plugin\admin\app\model\Address;
use support\Request;

class AddressController extends BaseController
{
    function add(Request $request)
    {
        $name = $request->post('name');
        $mobile = $request->post('mobile');
        $province = $request->post('province');
        $city = $request->post('city');
        $region = $request->post('region');
        $detail = $request->post('detail');
        $default = $request->post('default', 0);
        if (!$name || !$mobile || !$province || !$city || !$region || !$detail) {
            return $this->fail('参数错误');
        }
        $data = [
            'name' => $name,
            'mobile' => $mobile,
            'province' => $province,
            'city' => $city,
            'region' => $region,
            'detail' => $detail,
            'user_id' => $request->uid,
        ];
        if ($default == 0) {
            $row = Address::where(['user_id' => $request->uid, 'default' => 1])->first();
            if (!$row) {
                $data['default'] = 1;
            }
        } else {
            Address::where(['user_id' => $request->uid, 'default' => 1])->update(['default' => 0]);
            $data['default'] = 1;
        }
        Address::create($data);
        return $this->success();
    }

    /**
     * 设置默认地址
     */
    function setDefault(Request $request)
    {
        $id = $request->post('id');
        Address::where(['user_id' => $request->uid])->update(['default' => 0]);
        Address::where(['id' => $id])->update(['default' => 1]);
        return $this->success();
    }

    /**
     * 获取默认地址
     */
    function getDefault(Request $request)
    {
        $row = Address::where(['user_id' => $request->uid, 'default' => 1])->first();
        return $this->success('成功', $row);
    }

    /**
     * 获取指定地址
     */
    function get(Request $request)
    {
        $id = $request->get('id');
        $row = Address::find($id);
        if (!$row){
            return $this->fail('地址不存在');
        }
        return $this->success('成功', $row);
    }

    /**
     * 编辑地址
     */
    function edit(Request $request)
    {
        $id = $request->post('id');
        $default = $request->post('default');
        $detail = $request->post('detail');
        $province = $request->post('province');
        $city = $request->post('city');
        $region = $request->post('region');
        $mobile = $request->post('mobile');
        $name = $request->post('name');


        $row = Address::find($id);
        if (!$row){
            return $this->fail('地址不存在');
        }
        if (isset($default)&&$default == 1) {
            Address::where(['user_id' => $request->uid])->update(['default' => 0]);
        }
        $row->default = $default;
        $row->detail = $detail;
        $row->province = $province;
        $row->city = $city;
        $row->region = $region;
        $row->mobile = $mobile;
        $row->name = $name;
        $row->save();
        return $this->success();
    }

    /**
     * 删除地址
     */
    function delete(Request $request)
    {
        $id = $request->post('id/d');
        $row = Address::where(['user_id' => $request->uid])->find($id);
        if (!$row){
            return $this->fail('地址不存在');
        }
        $row->delete();
        return $this->success();
    }

    /**
     * 地址列表
     */
    function getList(Request $request)
    {
        $rows = Address::where(['user_id' => $request->uid])->orderByDesc('id')
            ->paginate()
            ->items();
        return $this->success('成功',$rows);
    }

}
