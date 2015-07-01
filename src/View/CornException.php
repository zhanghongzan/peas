<?php
namespace Peas\View;

/**
 * Peas Framework
 *
 * 模板引擎异常
 *
 * 异常编码：
 * 101  模板文件未找到
 * 102  编译文件写入失败
 * 103  缓存文件写入失败
 * 1021 开始标签无法识别
 * 1022 结束标签无法识别
 * 1023 写入合并css文件失败
 * 1024 写入合并js文件失败
 * 1025 内置标签解析失败-标签无法匹配
 * 1026 内置标签解析失败-参数有误
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class CornException extends \Exception
{
    public function __construct($message, $code = 100)
    {
        parent::__construct($message, $code);
    }
}
