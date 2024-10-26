<?php

namespace plugin\oplog\api;

use Chance\Log\facades\OperationLog;
use plugin\admin\api\Menu;
use plugin\admin\app\common\Util;
use plugin\oplog\app\controller\OperationLogController;

class Install
{
    /**
     * 安装
     *
     * @return void
     */
    public static function install()
    {
        self::composer();
        self::createTable();
        self::copyFile();

        if (Menu::get(OperationLogController::class)) {
            return;
        }
        // 找到权限管理菜单
        $authMenu = Menu::get('auth');
        if (!$authMenu) {
            echo "未找到权限管理菜单" . PHP_EOL;
            return;
        }
        // 以权限管理菜单为上级菜单插入菜单
        $pid = $authMenu['id'];
        Menu::add([
            'title' => '操作日志',
            'href' => '/app/oplog/operation-log/index',
            'pid' => $pid,
            'key' => OperationLogController::class,
            'weight' => 0,
            'type' => 1,
        ]);
    }

    /**
     * 安装composer包
     * @return void
     */
    protected static function composer()
    {
        $composer = [];
        if (!class_exists(OperationLog::class)) {
            $composer[] = "chance-fyi/operation-log";
        }
        array_map(function ($v) {
            self::exec("composer require $v");
        }, $composer);
    }

    protected static function exec($cmd)
    {
        $desc = [
            0 => STDIN,
            1 => STDOUT,
            2 => STDOUT,
        ];
        $pipes = [];
        $handler = proc_open("$cmd", $desc, $pipes, base_path());
        if (!is_resource($handler)) {
            echo "请执行 $cmd";
            return;
        }
        proc_close($handler);
    }

    public static function createTable()
    {
        $sql_file = base_path() . '/plugin/oplog/install.sql';
        $sql_query = file_get_contents($sql_file);
        foreach (explode(";", $sql_query) as $sql) {
            $sql = trim($sql);
            if (empty($sql)) {
                continue;
            }
            Util::db()->select($sql);
        }
    }

    public static function copyFile()
    {
        $source = base_path() . '/plugin/oplog/app/command/TableModelMapping.php';
        $destDir = base_path() . '/app/command';
        if (!is_dir($destDir)) {
            mkdir($destDir);
        }
        $dest = $destDir . '/TableModelMapping.php';
        copy($source, $dest);
    }

    /**
     * 卸载
     *
     * @return void
     */
    public static function uninstall()
    {
        // 删除菜单
        Menu::delete(OperationLogController::class);
    }
}