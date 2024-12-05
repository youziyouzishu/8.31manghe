<?php

namespace app\controller;

use plugin\admin\app\model\Option;
use support\Request;

class ConfigController extends BaseController
{
    protected array $noNeedLogin = ['getAgreement'];
    function getAgreement()
    {
        $name = 'system_config';
        $config = Option::where('name', $name)->value('value');
        $config = json_decode($config);
        return $this->success('成功', [
            'privacy_agreement' => $config->logo->privacy_agreement,
            'user_agreement' => $config->logo->user_agreement,
            'pay_agreement' => $config->logo->pay_agreement,
            'diy_explain' =>$config->logo->diy_explain,
            'box_explain' =>$config->logo->box_explain,
            'buy_explain' => $config->logo->buy_explain,
            'newfuli' => $config->logo->newfuli,
            'start_page'=>$config->logo->start_page,
        ]);
    }
}
