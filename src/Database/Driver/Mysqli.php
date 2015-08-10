<?php
namespace Peas\Database\Driver;

use Peas\Database\DbException;
use Peas\Database\Debug;

/**
 * Peas Framework
 *
 * Mysqli操作类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Mysqli implements DriverInterface
{
    /**
     * 数据库连接
     *
     * @var resource
     */
    private $_link = null;

    /**
     * 当前查询标识符
     *
     * @var resource
     */
    private $_queryId = null;

    /**
     * 最近执行的SQL语句
     *
     * @var string
     */
    private $_sql = '';

    /**
     * 事务指令数
     *
     * @var int
     */
    private $_transTimes;

    /**
     * 数据库版本信息
     *
     * @var string
     */
    private $_version = '';


    /**
     * 初始化连接
     *
     * @param array $config 配置参数 [<br>
     *     'host'      => 'localhost', // 服务器地址<br>
     *     'port'      => 3306,        // 端口<br>
     *     'username'  => 'root',      // 用户名<br>
     *     'password'  => 'admin',     // 密码<br>
     *     'database'  => 'peas',      // 数据库名<br>
     *     'charset'   => 'utf8',      // 数据库编码默认采用utf8<br>
     *     'pcconnect' => false,       // 持久连接<br>
     * ]<br>
     * @throws DbException 201:不支持mysqli时抛出，202:连接数据库出错时抛出
     */
    public function __construct(array $config = [])
    {
        if (!class_exists('mysqli')) {
            throw new DbException('[Db]不支持mysqli', 201);
        }
        $this->_link = new mysqli($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);
        if (mysqli_connect_errno()) {
            throw new DbException('[MySqli]连接数据库[' . $config['host'] . '.' . $config['database'] . ']出错：' . mysqli_connect_error(), 202);
        }
        if ($this->getVersion() > '4.1') {
            $this->_link->query("SET NAMES '" . $config['charset'] . "'");
        }
        if ($this->getVersion() > '5.0.1') {
            $this->_link->query("SET sql_mode=''");
        }
    }

    /**
     * 析构：关闭连接
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @see DriverInterface::getVersion()
     */
    public function getVersion()
    {
        if (empty($this->_version)) {
            $this->_version = $this->_link->server_version;
        }
        return $this->_version;
    }

    /**
     * @see DriverInterface::getLink()
     */
    public function getLink()
    {
        return $this->_link;
    }

    /**
     * @see DriverInterface::getError()
     */
    public function getError()
    {
        return $this->_link->error;
    }

    /**
     * @see DriverInterface::getSql()
     */
    public function getSql()
    {
        return $this->_sql;
    }

    /**
     * 执行SQL语句
     *
     * @param  string $sql
     * @return resource
     * @throws DbException 204 执行失败时抛出
     */
    private function _doExecute($sql)
    {
        if (!$this->_link) {
            throw new DbException('[MySqli]SQL执行失败：数据库连接有误', 204);
        }
        if ($this->_queryId) {
            $this->free();
        }
        $startTime = microtime(true);
        $result = $this->_link->query($sql);
        Debug::debug($sql, $startTime, microtime(true));

        if (false === $result) {
            throw new DbException('[MySqli]SQL执行失败：' . $this->getError(), 204);
        }
        $this->_sql = $sql;
        return $result;
    }

    /**
     * @see DriverInterface::execute()
     */
    public function execute($sql)
    {
        $this->_doExecute($sql);
        Debug::$writeNum ++;
        return $this->_link->affected_rows;
    }

    /**
     * 执行查询语句
     *
     * @param  string $sql
     * @return void
     */
    private function _query($sql)
    {
        $result = $this->_doExecute($sql);
        $this->_queryId = $result;
        Debug::$queryNum ++;
        return $this->_queryId->num_rows;
    }

    /**
     * 获取查询结果集
     *
     * @param  string $className 对象名，不为空表示获取对象形式的结果集，为空表示获取数组形式的结果集，默认为空
     * @param  array  $params 获取对象形式结果时，传入构造函数的参数
     * @return array  结果集
     */
    private function _getAll($className = '', array $params = [])
    {
        $result  = [];
        $numRows = $this->_queryId->num_rows;
        if ($numRows > 0) {
            if (empty($className)) {
                for ($i = 0; $i < $numRows; $i ++) {
                    $result[$i] = $this->_queryId->fetch_assoc();
                }
            } else {
                for ($i = 0; $i < $numRows; $i ++) {
                    if ($className == 'stdClass') {
                        $result[$i] = $this->_queryId->fetch_object();
                    } else {
                        $result[$i] = empty($params) ? $this->_queryId->fetch_object($className) : $this->_queryId->fetch_object($className, $params);
                    }
                }
            }
            $this->_queryId->data_seek(0);
        }
        return $result;
    }

    /**
     * @see DriverInterface::getNumRows()
     */
    public function getNumRows($sql)
    {
        return $this->_query($sql);
    }

    /**
     * @see DriverInterface::select()
     */
    public function select($sql)
    {
        $this->_query($sql);
        return $this->_getAll();
    }

    /**
     * @see DriverInterface::selectForObject()
     */
    public function selectForObject($sql, $className = '', array $params = [])
    {
        $this->_query($sql);
        return $this->_getAll(empty($className) ? 'stdClass' : $className, $params);
    }

    /**
     * @see DriverInterface::update()
     */
    public function update($sql)
    {
        return $this->execute($sql);
    }

    /**
     * @see DriverInterface::delete()
     */
    public function delete($sql)
    {
        return $this->execute($sql);
    }

    /**
     * @see DriverInterface::insert()
     */
    public function insert($sql)
    {
        $this->execute($sql);
        return $this->_link->insert_id;
    }

    /**
     * @see DriverInterface::rollback()
     */
    public function rollback()
    {
        if ($this->_transTimes > 0) {
            $result = $this->_link->rollback();
            if(!$result) {
                throw new DbException('[MySqli]事务回滚失败：' . $this->getError(), 206);
            }
            $this->_transTimes = 0;
        }
    }

    /**
     * @see DriverInterface::startTrans()
     */
    public function startTrans()
    {
        if (!$this->_link) {
            return false;
        }
        if ($this->_transTimes == 0) {
            $this->_link->autocommit(false);
        }
        $this->_transTimes++;
        return true;
    }

    /**
     * @see DriverInterface::commit()
     */
    public function commit()
    {
        if ($this->_transTimes > 0) {
            $result = $this->_link->commit();
            $this->_link->autocommit(true);
            if(!$result){
                throw new DbException('[MySqli]事务提交失败：' . $this->getError(), 205);
            }
            $this->_transTimes = 0;
        }
    }

    /**
     * @see DriverInterface::free()
     */
    public function free()
    {
        @mysqli_free_result($this->_queryId);
        $this->_queryId = null;
    }

    /**
     * @see DriverInterface::close()
     */
    public function close()
    {
        if (!empty($this->_queryId)) {
            $this->_queryId->free_result();
        }
        if ($this->_link && !$this->_link->close()) {
            throw new DbException('[MySqli]关闭连接失败：' . $this->getError(), 203);
        }
        $this->_link = null;
    }

    /**
     * @see DriverInterface::escapeString()
     */
    public function escapeString($str)
    {
        return ($this->_link) ? $this->_link->real_escape_string($str) : addslashes($str);
    }
}
