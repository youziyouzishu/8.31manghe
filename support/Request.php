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
     * @param string $method
     * @param array $data
     */
    function set(string $method, array $data)
    {
        $method = strtolower($method);
        $newData = $this->_data; // 复制原始数据
        $newMethodData = array_merge($newData[$method] ?? [], $data); // 合并特定方法的数据
        $this->_data[$method] = $newMethodData; // 更新对象数据
    }
}