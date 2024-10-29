<?php

namespace plugin\admin\app\controller;

use EasyWeChat\MiniApp\Application;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use plugin\admin\app\model\Deliver;
use plugin\admin\app\model\User;
use plugin\admin\app\model\UsersDisburse;
use plugin\admin\app\model\UsersMoneyLog;
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
        if (!empty(request()->get('user_disburse_at'))) {
            $user_disburse_at = request()->get('user_disburse_at');
        } else {
            $user_disburse_at = null;
        }

        $query = $this->doSelect($where, $field, $order)
            ->with(['children'])
            ->withSum(['userDisburse as today_user_disburse_sum_amount' => function ($query) use ($where) {
                $query->where('type', 1)->whereDate('created_at', date('Y-m-d'));
            }], 'amount')
            ->withSum(['userDisburse as user_disburse_sum_amount' => function ($query) {
                $query->where('type', 1);
            }], 'amount')
            ->withSum(['userDisburse as today_user_disburse_sum_amount_amount' => function ($query) {
                $query->where('mark', '<>', '购买商品')->whereDate('created_at', date('Y-m-d'));
            }], 'amount')
            ->withSum(['userDisburse as user_disburse_sum_amount_amount' => function ($query) {
                $query->where('mark', '<>', '购买商品');
            }], 'amount')
            ->when(!empty($user_disburse_at), function ($query) use ($user_disburse_at) {
                $query
                    ->withSum(['userDisburse as user_disburse_sum_amount_at' => function ($query) use ($user_disburse_at) {
                        $query->whereBetween('created_at', [$user_disburse_at[0], $user_disburse_at[1]])->where('type', 1);
                    }], 'amount')
                    ->withSum(['userDisburse as user_disburse_sum_amount_amount_at' => function ($query) use ($user_disburse_at) {
                        $query->whereBetween('created_at', [$user_disburse_at[0], $user_disburse_at[1]])->where('mark', '<>', '购买商品');
                    }], 'amount');
            })
            ->withSum('userPrize', 'price');

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

        return collect($items)->each(function ($item) {
            if (!empty(request()->get('profit_created_at'))) {
                $where['profit_created_at'] = request()->get('profit_created_at');
                //微信支付的金额
                $profit_sum_amount = UsersDisburse::where(['type' => 1, 'user_id' => $item->id])->whereBetween('created_at', [$where['profit_created_at'][0], $where['profit_created_at'][1]])->sum('amount');
                //用户选择发货的赏品价值
                $deliver_amount = 0;
                Deliver::where('user_id', $item->id)->whereIn('status', [1, 2, 3])->whereBetween('created_at', [$where['profit_created_at'][0], $where['profit_created_at'][1]])->withSum('usersPrize', 'price')->get()->each(function ($item) use (&$deliver_amount) {
                    $deliver_amount += $item->users_prize_sum_price;
                });
                //赠送好友的赏品价值
                $give_amount = UsersPrizeLog::where(['user_id' => $item->id, 'type' => 1])->whereBetween('created_at', [$where['profit_created_at'][0], $where['profit_created_at'][1]])->sum('price');
                //水晶余额
                $money = $item->money;
                //赏袋和保险箱剩余商品价值
                $user_prize_sum_price = $item->user_prize_sum_price;
                //活动赠送部分
                $give_prize = UsersPrizeLog::where('user_id', $item->id)->where('type', 3)->whereBetween('created_at', [$where['profit_created_at'][0], $where['profit_created_at'][1]])->get();
                $give_prize_price = $give_prize->sum('price');
                //系统增加的水晶
                $system_money = UsersMoneyLog::where(['user_id' => $item->id, 'memo' => '系统赠送'])->whereBetween('created_at', [$where['profit_created_at'][0], $where['profit_created_at'][1]])->sum('money') ?? 0;

                $item->profit = abs($profit_sum_amount) - $deliver_amount - $give_amount - $money - $user_prize_sum_price - $give_prize_price - $system_money;
                $item->give_prize = $give_prize;
            } else {
                //微信支付的金额
                $profit_sum_amount = UsersDisburse::where(['type' => 1, 'user_id' => $item->id])->sum('amount');
                //用户选择发货的赏品价值
                $deliver_amount = 0;
                Deliver::where('user_id', $item->id)->whereIn('status', [1, 2, 3])->withSum('usersPrize', 'price')->get()->each(function ($item) use (&$deliver_amount) {
                    $deliver_amount += $item->users_prize_sum_price;
                });
                //赠送好友的赏品价值
                $give_amount = UsersPrizeLog::where(['user_id' => $item->id, 'type' => 1])->sum('price');
                //水晶余额
                $money = $item->money;
                //赏袋和保险箱剩余商品价值
                $user_prize_sum_price = $item->user_prize_sum_price;
                //活动赠送部分
                $give_prize = UsersPrizeLog::where('user_id', $item->id)->where('type', 3)->get();
                $give_prize_price = $give_prize->sum('price');
                //系统增加的水晶
                $system_money = UsersMoneyLog::where(['user_id' => $item->id, 'memo' => '系统赠送'])->sum('money') ?? 0;


                $item->profit = abs($profit_sum_amount) - $deliver_amount - $give_amount - $money - $user_prize_sum_price - $give_prize_price - $system_money;
                $item->give_prize = $give_prize;
            }
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
            $user = $this->model->find($request->input('id'));
            $originmoney = $user->money;
            $changemoney = $request->post('money');

            if (!empty($changemoney) && (function_exists('bccomp') ? bccomp($changemoney, $originmoney, 2) !== 0 : (double)$changemoney !== (double)$originmoney)) {

                UsersMoneyLog::create(['user_id' => $user->id, 'money' => $changemoney - $originmoney, 'before' => $originmoney, 'after' => $changemoney, 'memo' => '系统赠送']);
            }
            return parent::update($request);
        }
        return view('user/update');
    }

    function getwxacodeunlimit(Request $request)
    {
        $id = $request->post('id');
        $app = new Application(config('wechat'));
        try {
            $response = $app->getClient()->postJson('/wxa/getwxacodeunlimit', [
                'scene' => 'login',
                'page' => 'pages/index/index',
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
                    'memo' => '管理员变更'
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


}
