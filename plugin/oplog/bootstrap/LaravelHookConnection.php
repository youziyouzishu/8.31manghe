<?php

namespace plugin\oplog\bootstrap;

use Chance\Log\orm\illuminate\MySqlConnection;
use Illuminate\Database\Connection;
use Webman\Bootstrap;
use Workerman\Worker;

class LaravelHookConnection implements Bootstrap
{

    public static function start(?Worker $worker)
    {
        Connection::resolverFor('mysql', function ($connection, $database, $prefix, $config) {
            return new MySqlConnection($connection, $database, $prefix, $config);
        });
    }
}