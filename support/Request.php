<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace support;

/**
 * Class Request
 * @package support
 */
class Request extends \Webman\Http\Request
{
    /**
     * 设置$request数据，自动覆盖更新
     * @param array $data
     */
    function set(array $data)
    {
        $key = key($data);// 获取数组的键名
        $rawData = $this->$key ?: [];// 获取原数据
        $data = array_merge($rawData, $data[$key]);// 合并新数据
        $this->$key = $data; // 设置新数据
    }
}