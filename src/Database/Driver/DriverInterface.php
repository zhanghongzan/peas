<?php
namespace Peas\Database\Driver;

/**
 * Peas Framework
 *
 * 数据层接口
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
interface DriverInterface
{
    /**
     * 获取数据库版本信息
     *
     * @return string 版本号
     */
    public function getVersion();

    /**
     * 获取当前连接
     *
     * @return resource|PDO
     */
    public function getLink();

    /**
     * 执行查询语句，获取数组形式的结果集
     *
     * @param  string $sql SQL语句
     * @return array 数组形式的结果集数组
     * @throws DbException 204 执行失败时抛出
     */
    public function select($sql);

    /**
     * 获取结果数
     *
     * @param  string $sql SQL语句
     * @return int 结果数
     * @throws DbException 204 执行失败时抛出
     */
    public function getNumRows($sql);

    /**
     * 执行查询语句，获取对象形式的结果集
     *
     * @param  string $sql       SQL语句
     * @param  string $className 要实例化的类的名称，如果没有指定，返回stdClass对象
     * @param  array  $params    可选的传递给$className对象的构造函数参数数组
     * @return array 对象形式的结果集数组
     * @throws DbException 204 执行失败时抛出
     */
    public function selectForObject($sql, $className = '', array $params = []);

    /**
     * 执行插入语句
     *
     * @param  string $sql SQL语句
     * @return int 产生的AUTO_INCREMENT值，没有则为0
     * @throws DbException 204 执行失败时抛出
     */
    public function insert($sql);

    /**
     * 执行更新语句
     *
     * @param  string $sql SQL语句
     * @return int 影响到多少行
     * @throws DbException 204 执行失败时抛出
     */
    public function update($sql);

    /**
     * 执行删除语句
     *
     * @param  string $sql SQL语句
     * @return int 影响到多少行
     * @throws DbException 204 执行失败时抛出
     */
    public function delete($sql);

    /**
     * 执行SQL语句
     *
     * @param  string $sql SQL语句
     * @return int 影响到多少行
     * @throws DbException 204 执行失败时抛出
     */
    public function execute($sql);

    /**
     * 启动事务
     *
     * @return boolean 启动成功返回TRUE，失败返回FALSE
     */
    public function startTrans();

    /**
     * 提交事务
     *
     * @return void
     * @throws DbException 205 失败时抛出
     */
    public function commit();

    /**
     * 事务回滚
     *
     * @return void
     * @throws DbException 206 失败时抛出
     */
    public function rollback();

    /**
     * 获取最近一次执行的SQL语句
     *
     * @return string 最近一次执行的SQL语句
     */
    public function getSql();

    /**
     * 获取最近的错误信息
     *
     * @return string 最近的错误信息
     */
    public function getError();

    /**
     * 释放查询结果
     *
     * @return void
     */
    public function free();

    /**
     * 关闭连接
     *
     * @return void
     * @throws DbException 203 关闭连接失败时抛出
     */
    public function close();

    /**
     * SQL安全过滤
     *
     * @param  string $str 需要过滤的字符串
     * @return string 经过过滤的字符串
     */
    public function escapeString($str);
}
