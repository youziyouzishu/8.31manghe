<?php

namespace app\controller;

use GuzzleHttp\Client;
use plugin\admin\app\model\Area;
use plugin\admin\app\model\Caiji;
use support\Db;
use support\Request;
use Webman\Push\Api;

class IndexController extends BaseController
{
    protected array $noNeedLogin = ['*'];
    public function index(Request $request)
    {
        $a = "1,2,3";
        $a = collect($a);
        $a = $a->each(function ($item){
            return $item;
        });
        return $this->success('采集完成');
        $area = Area::where(['level'=>2,'pass'=>0])->get();
        $client = new Client();
        foreach ($area as $v){
            for ($i=1;$i<=99999;$i++){
                $res = $client->request('GET','https://restapi.amap.com/v3/place/text?key=ad941626aed3c16b84c28562865314a3&keywords=防水&region='.$v->name.'&page_size=25&page_num='.$i.'&show_fields=children,business');
                $res = json_decode($res->getBody()->getContents());

                if ($res->status != '1') {
                    return $this->fail($res->info);
                }
                $pois = $res->pois;
                foreach ($pois as  $poi){
                    if (Caiji::where(['qid'=>$poi->id])->doesntExist()){
                        dump($poi);
                        Caiji::create([
                            'address'=>$poi->address?:'',
                            'name'=>$poi->name,
                            'qid'=>$poi->id,
                            'tel'=>$poi->tel?:'',
                        ]);
                    }
                }
                if ($res->count < 25) {
                    break;
                }
            }
            dump($v->name);
            $v->pass = 1;
            $v->save();
        }
        return $this->success('采集完成');
    }

}
