<?php
namespace Peas\Database;

/**
 * Peas Framework
 *
 * 数据库操作类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Db
{
    /**
     * 数据库连接
     *
     * @var DbLink
     */
    private static $_dbLink = null;


    /**
     * 初始化连接
     *
     * @param array            $config        配置参数
     * @param Peas\Cache\Cache $cacheInstance 缓存引擎实例
     */
    public static function init(array $config, $cacheInstance = null)
    {
        self::$_dbLink = new DbLink($config);
        self::$_dbLink->setCacheInstance($cacheInstance);
    }

    /**
     * 缓存设置
     *
     * @param  false|int 默认有效期|缓存有效期：秒（-1为永久）
     * @return DbLink
     */
    public static function cache($cacheLife = false)
    {
        return self::$_dbLink->cache($cacheLife);
    }

    /**
     * 分页设置，返回结果将为：[<br>
     *     'numRows'  => 数据总条数,<br>
     *     'page'     => 当前页码,<br>
     *     'pageSize' => 每页显示条数,<br>
     *     'result'   => 结果数据集,<br>
     * ]
     *
     * @param  false|int $page     不开启分页|页码
     * @param  int       $pageSize 开启分页时每页数量
     * @return DbLink
     */
    public static function page($page = false, $pageSize = 10)
    {
        return self::$_dbLink->page($page, $pageSize);
    }


    /**
     * 获取结果数量
     *
     * @param  string    $sql   SQL语句
     * @param  mixed     $param 参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @return int|false 结果数量，执行失败返回false
     */
    public static function count($sql, array $param = [])
    {
        return self::$_dbLink->count($sql, $param);
    }

    /**
     * 执行查询语句，获取数组形式结果集
     *
     * @param  string  $sql         SQL语句
     * @param  mixed   $param       参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @param  boolean $ifGetTop0   是否仅获取一条结果（默认为false）
     * @param  boolean $isGetObject 是否获取对象形式的结果（默认为false），true返回对象形式的结果，false返回数组形式的结果
     * @param  array   $resultMap   键名映射关系, ['字段名'=>'结果键名',...], 不设置表示不做任何改变(即'字段名'和'结果键名'是一样的)
     * @return mixed   成功返回单个结果或者结果集数组，失败返回false
     */
    public static function selectForArray($sql, array $param = [], $isGetTop0 = false, array $resultMap = [])
    {
        return self::$_dbLink->selectForArray($sql, $param, $isGetTop0, $resultMap);
    }

    /**
     * 执行查询语句，获取对象形式结果集
     *
     * @param  string  $sql         SQL语句
     * @param  mixed   $param       参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @param  boolean $ifGetTop0   是否仅获取一条结果（默认为false）
     * @param  string  $className   要实例化的类的名称，如果没有指定，返回stdClass对象
     * @param  array   $classParam  可选的传递给$className对象的构造函数参数数组
     * @param  array   $propertyMap 数据库字段名与object属性名映射关系，['字段名' => '属性名',...]
     * @return mixed   成功返回单个结果或者结果集数组，失败返回false
     */
    public static function selectForObject($sql, array $param = [], $isGetTop0 = false, $className = '', array $classParam = [], array $propertyMap = [])
    {
        return self::$_dbLink->selectForObject($sql, $param, $isGetTop0, $className, $classParam, $propertyMap);
    }

    /**
     * 执行插入语句
     *
     * @param  string    $sql   SQL语句
     * @param  mixed     $param 参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @return int|false 产生的AUTO_INCREMENT值，没有则为0，执行失败返回false
     */
    public static function insert($sql, array $param = [])
    {
        return self::$_dbLink->insert($sql, $param);
    }

    /**
     * 执行更新语句
     *
     * @param  string    $sql   SQL语句
     * @param  mixed     $param 参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @return int|false 影响到多少行，执行失败返回false
     */
    public static function update($sql, array $param = [])
    {
        return self::$_dbLink->update($sql, $param);
    }

    /**
     * 执行删除语句
     *
     * @param  string    $sql   SQL语句
     * @param  mixed     $param 参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @return int|false 影响到多少行，执行失败返回false
     */
    public static function delete($sql, array $param = [])
    {
        return self::$_dbLink->delete($sql, $param);
    }


    /**
     * 执行SQL语句
     *
     * @param  string    $sql   SQL语句
     * @param  mixed     $param 参数，多个参数用形如['paramName1' => 'value1', 'paramName2' => 'value2']的数组表示
     * @return int|false 影响到多少行，执行失败返回false
     */
    public static function execute($sql, array $param = [])
    {
        return self::$_dbLink->execute($sql, $param);
    }

    /**
     * 启动事务
     *
     * @return boolean 是否启动成功
     */
    public static function startTrans()
    {
        return self::$_dbLink->startTrans();
    }

    /**
     * 事务回滚
     *
     * @return boolean 是否执行成功
     */
    public static function rollback()
    {
        return self::$_dbLink->rollback();
    }

    /**
     * 提交事务
     *
     * @return boolean 是否提交成功
     */
    public static function commit()
    {
        return self::$_dbLink->commit();
    }

    /**
     * 获取最近一次执行的SQL语句
     *
     * @return string 最近一次执行的SQL语句
     */
    public static function getSql()
    {
        return self::$_dbLink->getSql();
    }

    /**
     * 获取最近的错误信息
     *
     * @return string 最近的错误信息
     */
    public static function getError()
    {
        return self::$_dbLink->getError();
    }

    /**
     * 释放查询结果
     *
     * @return void
     */
    public static function free()
    {
        self::$_dbLink->free();
    }

    /**
     * 关闭连接
     *
     * @return boolean 是否关闭成功
     */
    public static function close()
    {
        return self::$_dbLink->close();
    }

    /**
     * SQL安全过滤
     *
     * @param  string $str 需要过滤的字符串
     * @return string
     */
    public static function escapeString($str)
    {
        return self::$_dbLink->escapeString($str);
    }

    /**
     * 解析SQL语句，用参数替换SQL语句中的参数位<br>
     * 表名格式：##tableName##<br>
     * 参数格式：#paramName:type#<br>
     * #号字符串为\#<br>
     *
     * @param  string $sql   SQL语句
     * @param  mixed  $param 参数
     * @return string 解析后的Sql语句
     */
    public static function parseSql($sql, $param)
    {
        return self::$_dbLink->parseSql($sql, $param);
    }
}
