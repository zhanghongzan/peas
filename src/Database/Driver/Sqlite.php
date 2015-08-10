<?php
namespace Peas\Database\Driver;

use Peas\Database\DbException;
use Peas\Database\Debug;

/**
 * Peas Framework
 *
 * sqlite数据库操作类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Sqlite implements DriverInterface
{
    /**
     * 数据库打开模式
     *
     * @var int
     */
    public $mode = 0666;

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
     * 初始化连接
     *
     * @param  array $config 配置参数
     * @throws DbException 201:不支持Sqlite时抛出，202:连接数据库出错时抛出
     */
    public function __construct($config)
    {
        if (!extension_loaded('sqlite')) {
            throw new DbException('[Db]不支持Sqlite数据库', 201);
        }
        $functionName = $config['pcconnect'] ? 'sqlite_popen' : 'sqlite_open';
        $this->mode   = array_key_exists('mode', $config) ? $config['mode'] : $this->mode;
        $this->_link  = $functionName($config['database'], $this->mode);
        if (!$this->_link) {
            throw new DbException('[Sqlite]连接数据库[' . $config['database'] . ']出错', 202);
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
        return '';
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
        return sqlite_error_string(sqlite_last_error($this->_link));
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
     * @param  string   $sql
     * @param  boolean  $ifQuery 是否为查询
     * @return resource 执行结果
     * @throws DbException
     */
    private function _doExecute($sql, $ifQuery = false)
    {
        if (!$this->_link) {
            throw new DbException('[Sqlite]SQL执行失败：数据库连接有误', 204);
        }
        if ($this->_queryId) {
            $this->free();
        }
        $startTime = microtime(true);
        $result = $ifQuery ? sqlite_query($this->_link, $sql) : sqlite_exec($this->_link, $sql);
        Debug::debug($sql, $startTime, microtime(true));

        if (false === $result) {
            throw new DbException("[Sqlite]" . $this->getError(), 204);
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
        return sqlite_changes($this->_link);
    }

    /**
     * 执行查询语句
     *
     * @param  string $sql
     * @return int
     */
    private function _query($sql)
    {
        $result = $this->_doExecute($sql, true);
        $this->_queryId = $result;
        Debug::$queryNum ++;
        return sqlite_num_rows($this->_queryId);
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
        $result = [];
        $numRows = sqlite_num_rows($this->_queryId);
        if ($numRows > 0) {
            if (empty($className)) {
                for ($i = 0; $i < $numRows; $i ++) {
                    $result[$i] = sqlite_fetch_array($this->_queryId, SQLITE_ASSOC);
                }
            } else {
                for ($i = 0; $i < $numRows; $i ++) {
                    $result[$i] = sqlite_fetch_object($this->_queryId, $className, $params, SQLITE_ASSOC);
                }
            }
            sqlite_seek($this->_queryId, 0);
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
        return sqlite_last_insert_rowid($this->_link);
    }

    /**
     * @see DriverInterface::rollback()
     */
    public function rollback()
    {
        if ($this->_transTimes > 0) {
            $result = sqlite_query($this->_link, 'ROLLBACK TRANSACTION');
            if(!$result) {
                throw new DbException('[Sqlite]事务回滚失败：' . $this->getError(), 206);
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
            sqlite_query($this->_link, 'BEGIN TRANSACTION');
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
            $result = sqlite_query($this->_link, 'COMMIT TRANSACTION');
            if(!$result){
                throw new DbException('[Sqlite]事务提交失败：' . $this->getError(), 205);
            }
            $this->_transTimes = 0;
        }
    }

    /**
     * @see DriverInterface::free()
     */
    public function free()
    {
        $this->_queryId = null;
    }

    /**
     * @see DriverInterface::close()
     */
    public function close()
    {
        if ($this->_link && !sqlite_close($this->_link)){
            throw new DbException('[Sqlite]关闭连接失败：' . $this->getError(), 203);
        }
        $this->_link = null;
    }

    /**
     * @see DriverInterface::escapeString()
     */
    public function escapeString($str)
    {
        return sqlite_escape_string($str);
    }
}
