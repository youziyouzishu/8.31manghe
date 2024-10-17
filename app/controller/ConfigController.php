<?php

namespace app\controller;

use support\Request;

class ConfigController extends BaseController
{
    public function index(Request $request)
    {
        return response(__CLASS__);
    }

}
