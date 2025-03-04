<?php

namespace app\controller;

use plugin\admin\app\model\Address;
use support\Request;

class AddressController extends BaseController
{
    /**
     * 添加地址
     */
    function add(Request $request)
    {
        $requiredFields = ['name', 'mobile', 'province', 'city', 'region', 'detail'];
        foreach ($requiredFields as $field) {
            if (!$request->post($field)) {
                return $this->fail('参数错误');
            }
        }

        $data = [
            'name' => $request->post('name'),
            'mobile' => $request->post('mobile'),
            'province' => $request->post('province'),
            'city' => $request->post('city'),
            'region' => $request->post('region'),
            'detail' => $request->post('detail'),
            'user_id' => $request->user_id,
            'default' => $request->post('default', 0),
        ];

        if ($data['default'] == 0) {
            $existingDefault = Address::where(['user_id' => $request->user_id, 'default' => 1])->first();
            if (!$existingDefault) {
                $data['default'] = 1;
            }
        } else {
            Address::where(['user_id' => $request->user_id, 'default' => 1])->update(['default' => 0]);
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
        Address::where(['user_id' => $request->user_id])->update(['default' => 0]);
        Address::where(['id' => $id])->update(['default' => 1]);
        return $this->success();
    }

    /**
     * 获取默认地址
     */
    function getDefault(Request $request)
    {
        $row = Address::where(['user_id' => $request->user_id, 'default' => 1])->first();
        return $this->success('成功', $row);
    }

    /**
     * 获取指定地址
     */
    function get(Request $request)
    {
        $address_id = $request->post('address_id');
        $row = Address::find($address_id);
        if (!$row) {
            return $this->fail('地址不存在');
        }
        return $this->success('成功', $row);
    }

    /**
     * 编辑地址
     */
    function edit(Request $request)
    {
        $address_id = $request->post('address_id');
        $row = Address::find($address_id);
        if (!$row) {
            return $this->fail('地址不存在');
        }

        $fieldsToUpdate = [
            'name' => $request->post('name'),
            'mobile' => $request->post('mobile'),
            'province' => $request->post('province'),
            'city' => $request->post('city'),
            'region' => $request->post('region'),
            'detail' => $request->post('detail'),
            'default' => $request->post('default', 0),
        ];

        if ($fieldsToUpdate['default'] == 1) {
            Address::where(['user_id' => $request->user_id])->update(['default' => 0]);
        }

        $row->fill($fieldsToUpdate);
        $row->save();
        return $this->success();
    }

    /**
     * 删除地址
     */
    function delete(Request $request)
    {
        $id = $request->post('id');
        $row = Address::where(['user_id' => $request->user_id])->find($id);
        if (!$row) {
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
        $rows = Address::where(['user_id' => $request->user_id])
            ->orderByDesc('id')
            ->paginate()
            ->items();
        return $this->success('成功', $rows);
    }
}
