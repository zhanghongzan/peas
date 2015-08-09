<?php
namespace Peas\View;

use Peas\Support\Exception;

/**
 * Peas Framework
 *
 * 模板引擎异常
 *
 * 异常编码：<br>
 * 101  模板文件未找到<br>
 * 102  编译文件写入失败<br>
 * 103  缓存文件写入失败<br>
 * 1021 开始标签无法识别<br>
 * 1022 结束标签无法识别<br>
 * 1023 写入合并css文件失败<br>
 * 1024 写入合并js文件失败<br>
 * 1025 内置标签解析失败-标签无法匹配<br>
 * 1026 内置标签解析失败-参数有误<br>
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class CornException extends Exception
{
    public function __construct($message, $code = 100)
    {
        parent::__construct($message, $code);
    }
}
