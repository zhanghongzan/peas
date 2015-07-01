<?php
/**
 * Peas Framework
 *
 * sqlite数据库操作类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */

class Peas_System_Db_Sqlite implements Peas_System_Db_Interface
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
     * 初始化连接
     *
     * @param  array $config 配置参数
     * @throws Peas_System_Db_Exception 201:不支持Sqlite时抛出，202:连接数据库出错时抛出
     */
    public function __construct($config)
    {
        if (!extension_loaded('sqlite')) {
            throw new Peas_System_Db_Exception('[Db]不支持Sqlite数据库', 201);
        }
        $functionName = $config['pcconnect'] ? 'sqlite_popen' : 'sqlite_open';
        $this->mode   = array_key_exists('mode', $config) ? $config['mode'] : $this->mode;
        $this->_link  = $functionName($config['database'], $this->mode);
        if (!$this->_link) {
            throw new Peas_System_Db_Exception('[Sqlite]连接数据库[' . $config['database'] . ']出错', 202);
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
        return '';
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
        return sqlite_error_string(sqlite_last_error($this->_link));
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
     * @param  string  $sql
     * @param  boolean $ifQuery 是否为查询
     * @throws Peas_System_Db_Exception
     */
    private function _doExecute($sql, $ifQuery = FALSE)
    {
        if (!$this->_link) {
            throw new Peas_System_Db_Exception('[Sqlite]SQL执行失败：数据库连接有误', 204);
        }
        if ($this->_queryId) {
            $this->free();
        }
        $startTime = microtime(TRUE);
        $result = $ifQuery ? sqlite_query($this->_link, $sql) : sqlite_exec($this->_link, $sql);
        Peas_System_Db::_debug($sql, $startTime, microtime(TRUE));

        if (FALSE === $result) {
            throw new Peas_System_Db_Exception("[Sqlite]" . $this->getError(), 204);
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
        $result = $this->_doExecute($sql, TRUE);
        $this->_queryId = $result;
        Peas_System_Db::$queryNum ++;
        return sqlite_num_rows($this->_queryId);
    }

    /**
     * 获取查询结果集
     *
     * @return mixed 查询结果集
     */
    private function _getAll($className = '', $params = array())
    {
        $result = array();
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
        return sqlite_last_insert_rowid($this->_link);
    }

    /**
     * @see Peas_System_Db_Interface::rollback()
     */
    public function rollback()
    {
        if ($this->_transTimes > 0) {
            $result = sqlite_query($this->_link, 'ROLLBACK TRANSACTION');
            if(!$result) {
                throw new Peas_System_Db_Exception('[Sqlite]事务回滚失败：' . $this->getError(), 206);
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
            sqlite_query($this->_link, 'BEGIN TRANSACTION');
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
            $result = sqlite_query($this->_link, 'COMMIT TRANSACTION');
            if(!$result){
                throw new Peas_System_Db_Exception('[Sqlite]事务提交失败：' . $this->getError(), 205);
            }
            $this->_transTimes = 0;
        }
    }

    /**
     * @see Peas_System_Db_Interface::free()
     */
    public function free()
    {
        $this->_queryId = NULL;
    }

    /**
     * @see Peas_System_Db_Interface::close()
     */
    public function close()
    {
        if ($this->_link && !sqlite_close($this->_link)){
            throw new Peas_System_Db_Exception('[Sqlite]关闭连接失败：' . $this->getError(), 203);
        }
        $this->_link = NULL;
    }

    /**
     * @see Peas_System_Db_Interface::escapeString()
     */
    public function escapeString($str)
    {
        return sqlite_escape_string($str);
    }
}