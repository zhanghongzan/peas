<?php
/**
 * Peas Framework
 *
 * Mysqli操作类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */

class Peas_System_Db_Mysqli implements Peas_System_Db_Interface
{
    /**
     * 数据库连接
     *
     * @var resource
     */
    private $_link = NULL;

    /**
     * 当前查询标识符
     *
     * @var resource
     */
    private $_queryId = NULL;

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
     * @throws Peas_System_Db_Exception 201:不支持mysqli时抛出，202:连接数据库出错时抛出
     */
    public function __construct($config)
    {
        if (!class_exists('mysqli')) {
            throw new Peas_System_Db_Exception('[Db]不支持mysqli', 201);
        }
        $this->_link = new mysqli($config['host'], $config['username'], $config['password'], $config['database'], $config['port']);
        if (mysqli_connect_errno()) {
            throw new Peas_System_Db_Exception('[MySqli]连接数据库[' . $config['host'] . '.' . $config['database'] . ']出错：' . mysqli_connect_error(), 202);
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
     * @see Peas_System_Db_Interface::getVersion()
     */
    public function getVersion()
    {
        if (empty($this->_version)) {
            $this->_version = $this->_link->server_version;
        }
        return $this->_version;
    }

    /**
     * @see Peas_System_Db_Interface::getLink()
     */
    public function getLink()
    {
        return $this->_link;
    }

    /**
     * @see Peas_System_Db_Interface::getError()
     */
    public function getError()
    {
        return $this->_link->error;
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
     * @return resource
     * @throws Peas_System_Db_Exception 204 执行失败时抛出
     */
    private function _doExecute($sql)
    {
        if (!$this->_link) {
            throw new Peas_System_Db_Exception('[MySqli]SQL执行失败：数据库连接有误', 204);
        }
        if ($this->_queryId) {
            $this->free();
        }
        $startTime = microtime(TRUE);
        $result = $this->_link->query($sql);
        Peas_System_Db::_debug($sql, $startTime, microtime(TRUE));

        if (FALSE === $result) {
            throw new Peas_System_Db_Exception('[MySqli]SQL执行失败：' . $this->getError(), 204);
        }
        $this->_sql = $sql;
        return $result;
    }

    /**
     * @see Peas_System_Db_Interface::execute()
     */
    public function execute($sql)
    {
        $this->_doExecute($sql);
        Peas_System_Db::$writeNum ++;
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
        Peas_System_Db::$queryNum ++;
        return $this->_queryId->num_rows;
    }

    /**
     * 获取查询结果集
     *
     * @param  $className 对象名，不为空表示获取对象形式的结果集，为空表示获取数组形式的结果集，默认为空
     * @param  $params 获取对象形式结果时，传入构造函数的参数
     * @return array 结果集
     */
    private function _getAll($className = '', $params = array())
    {
        $result  = array();
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
     * @see Peas_System_Db_Interface::getNumRows()
     */
    public function getNumRows($sql)
    {
        return $this->_query($sql);
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
        return $this->_link->insert_id;
    }

    /**
     * @see Peas_System_Db_Interface::rollback()
     */
    public function rollback()
    {
        if ($this->_transTimes > 0) {
            $result = $this->_link->rollback();
            if(!$result) {
                throw new Peas_System_Db_Exception('[MySqli]事务回滚失败：' . $this->getError(), 206);
            }
            $this->_transTimes = 0;
        }
    }

    /**
     * @see Peas_System_Db_Interface::startTrans()
     */
    public function startTrans()
    {
        if (!$this->_link) {
            return FALSE;
        }
        if ($this->_transTimes == 0) {
            $this->_link->autocommit(false);
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
            $result = $this->_link->commit();
            $this->_link->autocommit(true);
            if(!$result){
                throw new Peas_System_Db_Exception('[MySqli]事务提交失败：' . $this->getError(), 205);
            }
            $this->_transTimes = 0;
        }
    }

    /**
     * @see Peas_System_Db_Interface::free()
     */
    public function free()
    {
        @mysqli_free_result($this->_queryId);
        $this->_queryId = NULL;
    }

    /**
     * @see Peas_System_Db_Interface::close()
     */
    public function close()
    {
        if (!empty($this->_queryId)) {
            $this->_queryId->free_result();
        }
        if ($this->_link && !$this->_link->close()) {
            throw new Peas_System_Db_Exception('[MySqli]关闭连接失败：' . $this->getError(), 203);
        }
        $this->_link = NULL;
    }

    /**
     * @see Peas_System_Db_Interface::escapeString()
     */
    public function escapeString($str)
    {
        return ($this->_link) ? $this->_link->real_escape_string($str) : addslashes($str);
    }
}