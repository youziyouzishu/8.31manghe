<?php

namespace app\controller;

use plugin\admin\app\model\Address;
use support\Db;
use support\Request;

class AddressController extends BaseController
{
    /**
     * 添加地址
     */
    function add(Request $request)
    {
        $name = $request->post('name');
        $mobile = $request->post('mobile');
        $province = $request->post('province');
        $city = $request->post('city');
        $region = $request->post('region');
        $detail = $request->post('detail');
        $default = $request->post('default', 0);
        $lat = $request->post('lat');
        $lng = $request->post('lng');
        $data = [
            'user_id' => $request->user_id,
            'name' => $name,
            'mobile' => $mobile,
            'province' => $province,
            'city' => $city,
            'region' => $region,
            'detail' => $detail,
            'default' => $default,
            'lat' => $lat,
            'lng' => $lng,
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
        Address::where(['user_id' => $request->user_id, 'default' => 1])->where('id','<>',$id)->update(['default' => 0]);
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
     * 详情
     */
    function detail(Request $request)
    {
        $id = $request->post('id');
        $row = Address::find($id);
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

        $id = $request->post('id');
        $name = $request->post('name');
        $mobile = $request->post('mobile');
        $province = $request->post('province');
        $city = $request->post('city');
        $region = $request->post('region');
        $detail = $request->post('detail');
        $default = $request->post('default', 0);

        $row = Address::find($id);
        if (!$row) {
            return $this->fail('地址不存在');
        }

        // 使用事务管理
        Db::connection('plugin.admin.mysql')->transaction(function () use ($request, $row, $name, $mobile, $province, $city, $region, $detail, $default) {
            // 删除旧记录并创建新记录
            $row->delete();
            $newRow = Address::create([
                'user_id' => $request->user_id,
                'name' => $name,
                'mobile' => $mobile,
                'province' => $province,
                'city' => $city,
                'region' => $region,
                'detail' => $detail,
                'default' => $default,
            ]);
            // 如果设置为默认地址，则将其他默认地址取消
            if ($default == 1) {
                Address::where([
                    ['user_id', $request->user_id],
                    ['default', 1],
                    ['id', '<>', $newRow->id]
                ])->update(['default' => 0]);
            }
        }, 3); // 设置重试次数以应对死锁等异常情况
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
