<?php
namespace Peas\Database;

use Peas\Support\PeasException;

/**
 * Peas Framework
 *
 * 数据层异常类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class DbException extends PeasException
{
    /**
     * 调用父类的构造方法
     *
     * @param string $message 异常信息
     * @param int $code 默认：200
     */
    public function __construct($message, $code = 200)
    {
        parent::__construct($message, $code);
    }
}
