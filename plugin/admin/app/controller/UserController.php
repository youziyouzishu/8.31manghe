<?php

namespace plugin\admin\app\controller;

use Carbon\Carbon;
use EasyWeChat\MiniApp\Application;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersDisburse;
use plugin\admin\app\model\UsersMoneyLog;
use plugin\admin\app\model\UsersPrize;
use plugin\admin\app\model\UsersPrizeLog;
use support\Db;
use support\exception\BusinessException;
use support\Request;
use support\Response;


/**
 * 用户
 */
class UserController extends Crud
{

    /**
     * @var User
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new User;
    }


    /**
     * 查询
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        $todayStart = Carbon::today()->startOfDay(); // 今天的开始时间
        $todayEnd = Carbon::today()->endOfDay(); // 今天的结束时间
        if (!empty(request()->get('user_disburse_at')[0])) {
            $user_disburse_at = request()->get('user_disburse_at');
        } else {
            $user_disburse_at = null;
        }
        $query = $this->doSelect($where, $field, $order)
            ->with(['children'])
            ->withSum(['userDisburse as today_user_disburse_sum_amount' => function ($query) use ($where, $todayStart, $todayEnd) {
                $query->where('type','<>',2)->whereBetween('created_at', [$todayStart, $todayEnd]);
            }], 'amount')
            ->withSum(['userDisburse as user_disburse_sum_amount' => function ($query) {
                $query->where('type','<>',2);
            }], 'amount')
            ->withSum(['userDisburse as today_user_disburse_sum_amount_amount' => function ($query) use ($todayStart, $todayEnd) {
                $query->where('scene', '<>', 2)->whereBetween('created_at', [$todayStart, $todayEnd]);
            }], 'amount')
            ->withSum(['userDisburse as user_disburse_sum_amount_amount' => function ($query) {
                $query->where('scene', '<>', 2);
            }], 'amount')
            ->when(!empty($user_disburse_at), function ($query) use ($user_disburse_at) {
                $query
                    ->withSum(['userDisburse as user_disburse_sum_amount_at' => function ($query) use ($user_disburse_at) {
                        $query->whereBetween('created_at', [$user_disburse_at[0], $user_disburse_at[1]])->whereIn('type', [1, 3]);
                    }], 'amount')
                    ->withSum(['userDisburse as user_disburse_sum_amount_amount_at' => function ($query) use ($user_disburse_at) {
                        $query->whereBetween('created_at', [$user_disburse_at[0], $user_disburse_at[1]])->where('scene', '<>', 2);
                    }], 'amount');
            });


        return $this->doFormat($query, $format, $limit);
    }


    /**
     * 执行真正查询，并返回格式化数据
     * @param $query
     * @param $format
     * @param $limit
     * @return Response
     */
    protected function doFormat($query, $format, $limit): Response
    {
        $methods = [
            'select' => 'formatSelect',
            'tree' => 'formatTree',
            'table_tree' => 'formatTableTree',
            'normal' => 'formatNormal',
        ];
        $paginator = $query->paginate($limit);
        $total = $paginator->total();
        $items = $paginator->items();
        if (method_exists($this, "afterQuery")) {
            $items = call_user_func([$this, "afterQuery"], $items);
        }
        $format_function = $methods[$format] ?? 'formatNormal';
        return call_user_func([$this, $format_function], $items, $total);
    }


    /**
     * 查询数据库后置方法，可用于修改数据
     * @param mixed $items 原数据
     * @return mixed 修改后数据
     */
    protected function afterQuery($items)
    {

        return collect($items)->each(function (User $item) {

            if (!empty(request()->get('profit_created_at')[0])) {
                $where['profit_created_at'] = request()->get('profit_created_at');
                dump('限制日期');
                dump(request()->get('profit_created_at'));
                //微信支付的金额
                $profit_sum_amount = UsersDisburse::where(['user_id' => $item->id])->whereIn('type', [1, 3])->whereBetween('created_at', [$where['profit_created_at'][0], $where['profit_created_at'][1]])->sum('amount');
                dump('微信支付的金额:'.$profit_sum_amount);

                //用户选择发货的赏品价值
                $deliver_amount = UsersPrizeLog::where(['user_id' => $item->id, 'type' => 4])->whereBetween('created_at', [$where['profit_created_at'][0], $where['profit_created_at'][1]])->selectRaw('SUM(num * price) as total_amount')->value('total_amount') ?? 0;

                dump('用户选择发货的赏品价值:'.$deliver_amount);
                //赠送好友的赏品价值
                $give_amount = UsersPrizeLog::where(['user_id' => $item->id, 'type' => 1])->whereBetween('created_at', [$where['profit_created_at'][0], $where['profit_created_at'][1]])->selectRaw('SUM(num * price) as total_amount')->value('total_amount') ?? 0;
                dump('赠送好友的赏品价值:'.$give_amount);
                //水晶余额
                $money = UsersMoneyLog::where(['user_id' => $item->id])->whereBetween('created_at', [$where['profit_created_at'][0], $where['profit_created_at'][1]])->orderByDesc('id')->value('after') ?? 0;
                dump('水晶余额:'.$money);
                //赏袋和保险箱剩余商品价值
                $user_prize_sum_price = UsersPrize::where(['user_id' => $item->id])
                    ->whereBetween('created_at', [$where['profit_created_at'][0], $where['profit_created_at'][1]])
                    ->select(DB::raw('SUM(price * num) as user_prize_sum_price'))
                    ->value('user_prize_sum_price') ?? 0;
                dump('赏袋和保险箱剩余商品价值:'.$user_prize_sum_price);
                //活动赠送部分
                $give_prize_price = UsersPrizeLog::where('user_id', $item->id)->where('type', 3)->whereBetween('created_at', [$where['profit_created_at'][0], $where['profit_created_at'][1]])->selectRaw('SUM(num * price) as total_amount')->value('total_amount') ?? 0;
                dump('活动赠送部分:'.$give_prize_price);
                //系统增加的水晶
                $system_money = UsersMoneyLog::where(['user_id' => $item->id,'memo' => '活动赠送'])->whereBetween('created_at', [$where['profit_created_at'][0], $where['profit_created_at'][1]])->sum('money') ?? 0;
            } else {
                dump('没有限制日期');
                dump('id:'.$item->id);
                //线上支付的金额
                $profit_sum_amount = UsersDisburse::where(['user_id' => $item->id])->whereIn('type', [1, 3])->sum('amount');
                dump('支付金额:'.$profit_sum_amount);

                //用户发货的赏品价值
                $deliver_amount = UsersPrizeLog::where(['user_id' => $item->id, 'type' => 4])->selectRaw('SUM(num * price) as total_amount')->value('total_amount') ?? 0;
                dump('发货'.$deliver_amount);

                //赠送好友的赏品价值
                $give_amount = UsersPrizeLog::where(['user_id' => $item->id, 'type' => 1])->selectRaw('SUM(num * price) as total_amount')->value('total_amount') ?? 0;
                dump('赠送好友的赏品价值:'.$give_amount);


                //水晶余额
                $money = $item->money;
                dump('水晶余额:'.$money);

                $user_prize_sum_price = UsersPrize::where(['user_id' => $item->id])->select(DB::raw('SUM(price * num) as user_prize_sum_price'))->value('user_prize_sum_price') ?? 0;
                dump('赏袋和保险箱剩余商品价值:'.$user_prize_sum_price);
                //系统赠送的奖品价值
                $give_prize_price = UsersPrizeLog::where('user_id', $item->id)->where('type', 3)->selectRaw('SUM(num * price) as total_amount')->value('total_amount') ?? 0;
                dump('系统赠送的奖品价值:'.$give_prize_price);
                //系统增加的水晶
                $system_money = UsersMoneyLog::where(['user_id' => $item->id,'memo' => '活动赠送'])->sum('money') ?? 0;


            }
            dump('活动赠送增加的水晶:'.$system_money);
            dump('支付金额-发货-赠送-水晶余额-赏袋和保险箱价值-系统活动赠送的奖品价值-活动赠送增加的水晶+系统活动赠送的奖品价值+活动赠送增加的水晶');
            $item->profit = round($profit_sum_amount - $deliver_amount - $give_amount - $money - $user_prize_sum_price - $system_money - $give_prize_price + $system_money + $give_prize_price, 2);
            dump('亏损计算:'.$item->profit);
            $item->user_prize_sum_price = $item->userPrize->sum(function ($userprize) {
                return $userprize->price * $userprize->num;
            });
        });
    }


    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('user/index');
    }

    /**
     * 浏览
     * @return Response
     */
    public function tree(): Response
    {
        return view('user/tree');
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::insert($request);
        }
        return view('user/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $params = $request->post();
            if (empty($params['parent_id'])) {
                $request->set('post', ['parent_id' => 0]);
            }
            $user = $this->model->find($params['id']);
            if ($user->id == $params['parent_id']) {
                return $this->fail('不能选择自己为上级');
            }

            $money = $request->post('money');
            if ($user->money != $money) {
                //变了账户
                $difference = $money - $user->money;
                User::money($difference, $user->id, $difference > 0 ? '活动赠送' : '系统扣除');
            }


            return parent::update($request);
        }
        return view('user/update');
    }

    function getwxacodeunlimit(Request $request)
    {
        $id = $request->post('id');
        $user = $this->model->find($id);
        $app = new Application(config('wechat'));
        try {
            $response = $app->getClient()->postJson('/wxa/getwxacodeunlimit', [
                'scene' => $user->invitecode,
                'page' => 'pages/login',
                'width' => 280,
                'check_path' => !config('app.debug'),

            ]);
            $response = base64_encode($response->getContent());
            return $this->success('生成成功', ['base64' => $response]);
        } catch (\Throwable $e) {
            // 失败
            return $this->fail($e->getMessage());
        }
    }

    function importe(Request $request)
    {
        $type = $request->input('type');
        $file = current($request->file());
        $ext = $file->getUploadExtension();

        $filePath = $file->getRealPath();
        //实例化reader
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            return $this->fail('文件格式错误');
        }
        if ($ext === 'csv') {
            $file = fopen($file->getRealPath(), 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp = fopen($filePath, 'w');
            $n = 0;
            while ($line = fgets($file)) {
                $line = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding !== 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if ($n == 0 || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ($ext === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        //导入文件首行类型,默认是注释,如果需要使用字段名称请使用name
        $importHeadType = isset($this->importHeadType) ? $this->importHeadType : 'comment';

        $table = $this->model->getTable();
        $database = config('plugin.admin.database.connections.mysql.database');
        $fieldArr = [];
        $list = DB::select("SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?", [$table, $database]);
        foreach ($list as $k => $v) {
            $fieldArr[$v->COLUMN_NAME] = $v->COLUMN_NAME;
        }

        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                return $this->fail('文件格式错误');
            }
            // 读取文件中的第一个工作表
            $currentSheet = $PHPExcel->getSheet(0);
            $allColumn = $currentSheet->getHighestDataColumn(); // 取得最大的列号
            $allRow = $currentSheet->getHighestRow(); // 取得一共有多少行
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);

            // 读取第一行作为字段名
            $fields = [];
            for ($currentRow = 1; $currentRow <= 1; $currentRow++) {
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $cellAddress = Coordinate::stringFromColumnIndex($currentColumn) . $currentRow;
                    $val = $currentSheet->getCell($cellAddress)->getValue();
                    $fields[] = $val;
                }
            }

            // 读取后续行的数据
            $insert = [];
            for ($currentRow = 2; $currentRow <= $allRow; $currentRow++) {
                $values = [];
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $cellAddress = Coordinate::stringFromColumnIndex($currentColumn) . $currentRow;
                    $val = $currentSheet->getCell($cellAddress)->getValue();
                    $values[] = is_null($val) ? '' : $val;
                }
                $row = [];
                $temp = array_combine($fields, $values);
                foreach ($temp as $k => $v) {
                    if (isset($fieldArr[$k]) && $k !== '') {
                        $row[$fieldArr[$k]] = $v;
                    }
                }
                if ($row) {
                    $insert[] = $row;
                }
            }
        } catch (\Throwable $exception) {
            return $this->fail($exception->getMessage());
        }
        if (!$insert) {
            return $this->fail('No rows were updated');
        }

        try {
            foreach ($insert as $k => $v) {
                $user = User::find($v['id']);
                $addmoney = $v['money'];
                $originmoney = $user->money;
                $user->increment('money', $addmoney);
                UsersMoneyLog::create([
                    'user_id' => $user->id,
                    'money' => $addmoney,
                    'before' => $originmoney,
                    'after' => $user->money,
                    'memo' => $type == 1 ? '活动赠送' : '补贴赠送',
                ]);
            }
        } catch (\PDOException $exception) {
            $msg = $exception->getMessage();
            if (preg_match("/.+Integrity constraint violation: 1062 Duplicate entry '(.+)' for key '(.+)'/is", $msg, $matches)) {
                $msg = "导入失败，包含【{$matches[1]}】的记录已存在";
            };
            return $this->fail($msg);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage());
        }
        return $this->success('导入成功');
    }


    /**
     * 修改中奖率
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function chance(Request $request): Response
    {
        $ids = $request->post('id');
        $chance = $request->post('chance');
        if ($chance < 0 || $chance > 100){
            return $this->fail('中奖率必须大于0且小于100');
        }
        $data['chance'] = $chance;
        $this->model->whereIn('id', $ids)->update($data);
        return $this->json(0);
    }


}
