<?php
/**
 * Peas Framework
 *
 * PDO封装类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */

class Peas_System_Db_Pdo implements Peas_System_Db_Interface
{
    /**
     * PDO实例
     *
     * @var PDO
     */
    private $_pdo = NULL;

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
     * @throws Peas_System_Db_Exception 201:不支持PDO时抛出，202:连接数据库出错时抛出
     */
    public function __construct($config)
    {
        if (!class_exists('PDO')) {
            throw new Peas_System_Db_Exception('[Db]不支持PDO', 201);
        }
        try {
            if ($config['pcconnect']) {
                $this->_pdo = new PDO($config['dsn'], $config['username'], $config['password'], array(PDO::ATTR_PERSISTENT => true));
            } else {
                $this->_pdo = new PDO($config['dsn'], $config['username'], $config['password']);
            }
            if (!empty($config['charset'])) {
                $this->_pdo->exec('SET NAMES ' . $config['charset']);
            }
        } catch (PDOException $e) {
            throw new Peas_System_Db_Exception('[PDO]连接数据库出错:' . $e->getMessage(), 202);
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
     * @see Peas_System_Db_Interface::getVersion()
     */
    public function getVersion()
    {
        if (empty($this->_version)) {
            $this->_version = $this->_pdo->getAttribute(PDO::ATTR_SERVER_INFO);
        }
        return $this->_version;
    }

    /**
     * @see Peas_System_Db_Interface::getLink()
     */
    public function getLink()
    {
        return $this->_pdo;
    }

    /**
     * @see Peas_System_Db_Interface::getError()
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
     * @see Peas_System_Db_Interface::getSql()
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
     * @throws Peas_System_Db_Exception
     */
    private function _doExecute($sql)
    {
        if (!$this->_pdo) {
            throw new Peas_System_Db_Exception('[PDO]SQL执行失败：数据库连接有误', 204);
        }
        if (!empty($this->_pdoStatement)) {
            $this->free();
        }

        $this->_sql = $sql;
        $startTime = microtime(TRUE);
        try {
            $result = $this->_pdo->prepare($sql);
            $result->execute();
        } catch (PDOException $e) {
            throw new Peas_System_Db_Exception('[PDO]SQL执行失败：' . $e->getMessage(), 204);
        }
        Peas_System_Db::_debug($sql, $startTime, microtime(TRUE));

        if ($result === FALSE) {
            throw new Peas_System_Db_Exception('[PDO]SQL执行失败：' . $this->getError(), 204);
        }
        return $result;
    }

    /**
     * @see Peas_System_Db_Interface::execute()
     */
    public function execute($sql)
    {
        $result = $this->_doExecute($sql);
        Peas_System_Db::$writeNum ++;
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
        Peas_System_Db::$queryNum ++;
    }

    /**
     * 获取查询结果集
     */
    private function _getAll($className = '', $params = array())
    {
        if (empty($className)) {
            return $this->_pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        }
        $result = array();
        $row = $this->_pdoStatement->fetchObject($className, $params);
        while ($row) {
            $result[] = $row;
            $row = $this->_pdoStatement->fetchObject($className, $params);
        }
        return $result;
    }

    /**
     * @see Peas_System_Db_Interface::getNumRows()
     */
    public function getNumRows($sql)
    {
        $sql = 'SELECT count(0) nums from (' . $sql . ') Peas_System_Db_e';
        $this->_query($sql);
        return $this->_pdoStatement->fetchObject()->nums;
    }

    /**
     * @see Peas_System_Db_Interface::select()
     */
    public function select($sql)
    {
        $this->_query($sql);
        return $this->_getAll();
    }

    /**
     * @see Peas_System_Db_Interface::selectForObject()
     */
    public function selectForObject($sql, $className = '', $params = array())
    {
        $this->_query($sql);
        return $this->_getAll(empty($className) ? 'stdClass' : $className, $params);
    }

    /**
     * @see Peas_System_Db_Interface::update()
     */
    public function update($sql)
    {
        return $this->execute($sql);
    }

    /**
     * @see Peas_System_Db_Interface::delete()
     */
    public function delete($sql)
    {
        return $this->execute($sql);
    }

    /**
     * @see Peas_System_Db_Interface::insert()
     */
    public function insert($sql)
    {
        $this->execute($sql);
        return $this->_pdo->lastInsertId();
    }

    /**
     * @see Peas_System_Db_Interface::rollback()
     */
    public function rollback()
    {
        if ($this->_transTimes > 0) {
            $result = $this->_pdo->rollBack();
            if (!$result) {
                throw new Peas_System_Db_Exception('[PDO]事务回滚失败：' . $this->getError(), 206);
            }
            $this->_transTimes = 0;
        }
    }

    /**
     * @see Peas_System_Db_Interface::startTrans()
     */
    public function startTrans()
    {
        if (!$this->_pdo) {
            return FALSE;
        }
        if ($this->_transTimes == 0) {
            $this->_pdo->beginTransaction();
        }
        $this->_transTimes++;
        return TRUE;
    }

    /**
     * @see Peas_System_Db_Interface::commit()
     */
    public function commit()
    {
        if ($this->_transTimes > 0) {
            $result = $this->_pdo->commit();
            if (!$result) {
                throw new Peas_System_Db_Exception('[PDO]事务提交失败：' . $this->getError(), 205);
            }
            $this->_transTimes = 0;
        }
    }

    /**
     * @see Peas_System_Db_Interface::free()
     */
    public function free()
    {
        $this->_pdoStatement = NULL;
    }

    /**
     * @see Peas_System_Db_Interface::close()
     */
    public function close()
    {
        if (!empty($this->_pdoStatement)) {
            $this->free();
        }
        $this->_pdo = NULL;
    }

    /**
     * @see Peas_System_Db_Interface::escapeString()
     */
    public function escapeString($str)
    {
        return addslashes($str);
    }
}