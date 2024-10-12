<?php

namespace app\controller;

use support\Request;

class UserController
{
    public function index(Request $request)
    {
        return response(__CLASS__);
    }

}
