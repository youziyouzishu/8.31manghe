<?php

namespace app\controller;

use plugin\admin\app\model\User;
use support\Request;

class IndexController
{
    public function index(Request $request)
    {

        static $readme;
        if (!$readme) {
            $readme = file_get_contents(base_path('README.md'));
        }
        return $readme;
    }

    public function view(Request $request)
    {
        $a = User::first();
        var_dump($a);
        return view('index/view', ['name' => 'webman']);
    }

    public function json(Request $request)
    {
        $a = User::first();
        var_dump($a);
        return json(['code' => 0, 'msg' => 'ok']);
    }

}
