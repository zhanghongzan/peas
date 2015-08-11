<?php
namespace Peas\Database\Driver;

use \PDO;
use Peas\Database\DbException;
use Peas\Database\DbDebug;

/**
 * Peas Framework
 *
 * PDO封装类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class PdoDriver implements DriverInterface
{
    /**
     * PDO实例
     *
     * @var PDO
     */
    private $_pdo = null;

    /**
     * 当前查询
     *
     * @var PDOStatement
     */
    private $_pdoStatement = null;

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
     * @param array $config 配置参数
     * @throws DbException 201:不支持PDO时抛出，202:连接数据库出错时抛出
     */
    public function __construct($config)
    {
        if (!class_exists('PDO')) {
            throw new DbException('[Db]不支持PDO', 201);
        }
        try {
            if ($config['pcconnect']) {
                $this->_pdo = new PDO($config['dsn'], $config['username'], $config['password'], [PDO::ATTR_PERSISTENT => true]);
            } else {
                $this->_pdo = new PDO($config['dsn'], $config['username'], $config['password']);
            }
            if (!empty($config['charset'])) {
                $this->_pdo->exec('SET NAMES ' . $config['charset']);
            }
        } catch (\PDOException $e) {
            throw new DbException('[PDO]连接数据库出错:' . $e->getMessage(), 202);
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
            $this->_version = $this->_pdo->getAttribute(PDO::ATTR_SERVER_INFO);
        }
        return $this->_version;
    }

    /**
     * @see DriverInterface::getLink()
     */
    public function getLink()
    {
        return $this->_pdo;
    }

    /**
     * @see DriverInterface::getError()
     */
    public function getError()
    {
        $errorArr = $this->_pdo->errorInfo();
        // 有错误信息
        if (count($errorArr) >= 3 && !empty($errorArr[2])) {
            return $errorArr[2] . '(SQL:' . $this->getSql() . ')';
        }
        return '';
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
     * @return void
     * @throws DbException
     */
    private function _doExecute($sql)
    {
        if (!$this->_pdo) {
            throw new DbException('[PDO]SQL执行失败：数据库连接有误', 204);
        }
        if (!empty($this->_pdoStatement)) {
            $this->free();
        }

        $this->_sql = $sql;
        $startTime = microtime(true);
        try {
            $result = $this->_pdo->prepare($sql);
            $result->execute();
        } catch (\PDOException $e) {
            throw new DbException('[PDO]SQL执行失败：' . $e->getMessage(), 204);
        }
        DbDebug::debug($sql, $startTime, microtime(true));

        if ($result === false) {
            throw new DbException('[PDO]SQL执行失败：' . $this->getError(), 204);
        }
        return $result;
    }

    /**
     * @see DriverInterface::execute()
     */
    public function execute($sql)
    {
        $result = $this->_doExecute($sql);
        DbDebug::$writeNum ++;
        return $result->rowCount();
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
        $this->_pdoStatement = $result;
        DbDebug::$queryNum ++;
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
        if (empty($className)) {
            return $this->_pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        }
        $result = [];
        $row = $this->_pdoStatement->fetchObject($className, $params);
        while ($row) {
            $result[] = $row;
            $row = $this->_pdoStatement->fetchObject($className, $params);
        }
        return $result;
    }

    /**
     * @see DriverInterface::getNumRows()
     */
    public function getNumRows($sql)
    {
        $sql = 'SELECT count(0) nums from (' . $sql . ') Peas_Database_E';
        $this->_query($sql);
        return $this->_pdoStatement->fetchObject()->nums;
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
        return $this->_pdo->lastInsertId();
    }

    /**
     * @see DriverInterface::rollback()
     */
    public function rollback()
    {
        if ($this->_transTimes > 0) {
            $result = $this->_pdo->rollBack();
            if (!$result) {
                throw new DbException('[PDO]事务回滚失败：' . $this->getError(), 206);
            }
            $this->_transTimes = 0;
        }
    }

    /**
     * @see DriverInterface::startTrans()
     */
    public function startTrans()
    {
        if (!$this->_pdo) {
            return false;
        }
        if ($this->_transTimes == 0) {
            $this->_pdo->beginTransaction();
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
            $result = $this->_pdo->commit();
            if (!$result) {
                throw new DbException('[PDO]事务提交失败：' . $this->getError(), 205);
            }
            $this->_transTimes = 0;
        }
    }

    /**
     * @see DriverInterface::free()
     */
    public function free()
    {
        $this->_pdoStatement = null;
    }

    /**
     * @see DriverInterface::close()
     */
    public function close()
    {
        if (!empty($this->_pdoStatement)) {
            $this->free();
        }
        $this->_pdo = null;
    }

    /**
     * @see DriverInterface::escapeString()
     */
    public function escapeString($str)
    {
        return addslashes($str);
    }
}
