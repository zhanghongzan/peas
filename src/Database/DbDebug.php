<?php
namespace Peas\Database;

/**
 * Peas Framework
 *
 * 数据库操作调试信息类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class DbDebug
{
    /**
     * 数据库查询次数
     *
     * @var int
     */
    public static $queryNum = 0;

    /**
     * 数据库更改次数
     *
     * @var int
     */
    public static $writeNum = 0;

    /**
     * 是否为调试模式
     *
     * @var boolean
     */
    public static $debug = false;

    /**
     * 执行的Sql语句，调试模式时执行的Sql语句存储到此数组中，每一条记录也为一个数组，array(sql，耗时)
     *
     * @var array
     */
    public static $sqls = array();


    /**
     * 调试模式时记录SQL日志
     *
     * @param  string $sql       SQL语句
     * @param  float  $startTime 执行开始时间
     * @param  float  $endTime   执行结束时间
     * @return void
     */
    public static function debug($sql = '', $startTime = 0, $endTime = 0)
    {
        if (self::$debug) {
            array_push(self::$sqls, array($sql, number_format($endTime - $startTime, 6)));
        }
    }
}
