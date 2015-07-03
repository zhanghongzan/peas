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
class Database
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
     * 数据库连接管理实例
     *
     * @var Peas_System_Db_Distribute
     */
    private $_distribute = NULL;

    /**
     * 上一Query是否通过只读连接操作
     *
     * @var boolean
     */
    private $_isPrevQueryRead = TRUE;

    /**
     * 缓存操作实例，若当前连接未启用缓存，则为NULL
     *
     * @var Peas_System_Cache_Interface
     */
    private $_cache = NULL;



    /**
     * 表前缀
     *
     * @var string
     */
    private $_prefix = '';

    /**
     * 表后缀
     *
     * @var string
     */
    private $_suffix = '';



    /**
     * 分页：页码
     *
     * @var FALSE|int 不开启分页|页码
     */
    private $_page = FALSE;

    /**
     * 开启分页时每页数量
     *
     * @var int
     */
    private $_pageSize = 10;

    /**
     * 当前查询是否开启缓存
     *
     * @var boolean
     */
    private $_ifCache = FALSE;

    /**
     * 当前查询缓存有效期
     *
     * @var FALSE|int 默认有效期|缓存有效期：秒（-1为永久）
     */
    private $_cacheLife = FALSE;



    /**
     * 初始化，获取默认数据库连接信息
     *
     * @param int $dbId 数据库ID
     */
    public function __construct($dbId) {
        $dbIdStr = '_db_' . $dbId;
        $this->_prefix     = Peas_System_Application::_getConfig($dbIdStr . '.prefix');
        $this->_suffix     = Peas_System_Application::_getConfig($dbIdStr . '.suffix');
        $this->_distribute = new Peas_System_Db_Distribute($dbId);

        if (Peas_System_Application::_getConfig($dbIdStr . '.cache') == TRUE) {
            try {
                $this->_cache = new Peas_Cache(Peas_System_Application::_getConfig($dbIdStr . '.cacheConfig'));
            } catch (Peas_System_Cache_Exception $e) {
                $e->printTraceToLog();
            }
        }
    }


    /**
     * 缓存设置
     *
     * @param  FALSE|int 默认有效期|缓存有效期：秒（-1为永久）
     * @return Peas_Dao
     */
    public function _cache($cacheLife = FALSE)
    {
        $this->_ifCache   = TRUE;
        $this->_cacheLife = $cacheLife;
        return $this;
    }

    /**
     * 分页设置，返回结果将为
     * array(
     *     'numRows'  => 数据总条数,
     *     'page'     => 当前页码,
     *     'pageSize' => 每页显示条数,
     *     'result'   => 结果数据集
     * );
     *
     * @param  FALSE|int $page 不开启分页|页码
     * @param  int $pageSize 开启分页时每页数量
     * @return Peas_Dao
     */
    public function _page($page = FALSE, $pageSize = 10)
    {
        $this->_page     = $page;
        $this->_pageSize = $pageSize;
        return $this;
    }


    /**
     * 获取结果数量
     *
     * @param  string  $sql SQL语句
     * @param  mixed   $param 参数，多个参数用形如array('paramName1' => 'value1', 'paramName2' => 'value2')的数组表示
     * @return int|FALSE 结果数量，执行失败返回FALSE
     */
    public function _count($sql, $param = array())
    {
        return $this->_getNumAndCatchException($this->_parseSql($sql, $param));
    }

    /**
     * 执行查询语句，获取数组形式结果集
     *
     * @param  string  $sql         SQL语句
     * @param  mixed   $param       参数，多个参数用形如array('paramName1' => 'value1', 'paramName2' => 'value2')的数组表示
     * @param  boolean $ifGetTop0   是否仅获取一条结果（默认为FALSE）
     * @param  boolean $isGetObject 是否获取对象形式的结果（默认为FALSE），TRUE返回对象形式的结果，FALSE返回数组形式的结果
     * @param  array   $resultMap   键名映射关系, array('字段名'=>'结果键名',...), 不设置表示不做任何改变(即'字段名'和'结果键名'是一样的)
     * @return mixed 成功返回单个结果或者结果集数组，失败返回FALSE
     */
    public function _selectForArray($sql, $param = array(), $isGetTop0 = FALSE, $resultMap = array())
    {
        return $this->_selectFactory($sql, $param, $isGetTop0, FALSE, $resultMap);
    }

    /**
     * 执行查询语句，获取对象形式结果集
     *
     * @param  string  $sql         SQL语句
     * @param  mixed   $param       参数，多个参数用形如array('paramName1' => 'value1', 'paramName2' => 'value2')的数组表示
     * @param  boolean $ifGetTop0   是否仅获取一条结果（默认为FALSE）
     * @param  string  $className   要实例化的类的名称，如果没有指定，返回stdClass对象
     * @param  array   $classParam  可选的传递给$className对象的构造函数参数数组
     * @param  array   $propertyMap 数据库字段名与object属性名映射关系，array('字段名' => '属性名',...)
     * @return mixed 成功返回单个结果或者结果集数组，失败返回FALSE
     */
    public function _selectForObject($sql, $param = array(), $isGetTop0 = FALSE, $className = '', $classParam = array(), $propertyMap = array())
    {
        return $this->_selectFactory($sql, $param, $isGetTop0, TRUE, array(), $className, $classParam, $propertyMap);
    }

    /**
     * 执行插入语句
     *
     * @param  string $sql SQL语句
     * @param  mixed  $param 参数，多个参数用形如array('paramName1' => 'value1', 'paramName2' => 'value2')的数组表示
     * @return int|FALSE 产生的AUTO_INCREMENT值，没有则为0，执行失败返回FALSE
     */
    public function _insert($sql, $param = array())
    {
        try {
            $this->_isPrevQueryRead = FALSE;
            return $this->_distribute->_getLink()->insert($this->_parseSql($sql, $param));
        } catch (Peas_System_Db_Exception $e) {
            $e->printTraceToLog();
            return FALSE;
        }
    }

    /**
     * 执行更新语句
     *
     * @param  string $sql SQL语句
     * @param  mixed  $param 参数，多个参数用形如array('paramName1' => 'value1', 'paramName2' => 'value2')的数组表示
     * @return int|FALSE 影响到多少行，执行失败返回FALSE
     */
    public function _update($sql, $param = array())
    {
        try {
            $this->_isPrevQueryRead = FALSE;
            return $this->_distribute->_getLink()->update($this->_parseSql($sql, $param));
        } catch (Peas_System_Db_Exception $e) {
            $e->printTraceToLog();
            return FALSE;
        }
    }

    /**
     * 执行删除语句
     *
     * @param  string $sql SQL语句
     * @param  mixed  $param 参数，多个参数用形如array('paramName1' => 'value1', 'paramName2' => 'value2')的数组表示
     * @return int|FALSE 影响到多少行，执行失败返回FALSE
     */
    public function _delete($sql, $param = array())
    {
        try {
            $this->_isPrevQueryRead = FALSE;
            return $this->_distribute->_getLink()->delete($this->_parseSql($sql, $param));
        } catch (Peas_System_Db_Exception $e) {
            $e->printTraceToLog();
            return FALSE;
        }
    }


    /**
     * 执行SQL语句
     *
     * @param  string $sql   SQL语句
     * @param  mixed  $param 参数，多个参数用形如array('paramName1' => 'value1', 'paramName2' => 'value2')的数组表示
     * @return int|FALSE 影响到多少行，执行失败返回FALSE
     */
    public function _execute($sql, $param = array())
    {
        try {
            $this->_isPrevQueryRead = FALSE;
            return $this->_distribute->_getLink()->execute($this->_parseSql($sql, $param));
        } catch (Peas_System_Db_Exception $e) {
            $e->printTraceToLog();
            return FALSE;
        }
    }

    /**
     * 启动事务
     *
     * @return boolean 是否启动成功
     */
    public function _startTrans()
    {
        return $this->_distribute->_getLink()->startTrans();
    }

    /**
     * 事务回滚
     *
     * @return boolean 是否执行成功
     */
    public function _rollback()
    {
        try {
            return $this->_distribute->_getLink()->rollback();
        } catch (Peas_System_Db_Exception $e) {
            $e->printTraceToLog();
            return FALSE;
        }
    }

    /**
     * 提交事务
     *
     * @return boolean 是否提交成功
     */
    public function _commit()
    {
        try {
            return $this->_distribute->_getLink()->commit();
        } catch (Peas_System_Db_Exception $e) {
            $e->printTraceToLog();
            return FALSE;
        }
    }

    /**
     * 获取最近一次执行的SQL语句
     *
     * @return string 最近一次执行的SQL语句
     */
    public function _getSql()
    {
        return $this->_isPrevQueryRead ? $this->_distribute->_getReadLink()->getSql() : $this->_distribute->_getLink()->getSql();
    }

    /**
     * 获取最近的错误信息
     *
     * @return string 最近的错误信息
     */
    public function _getError()
    {
        return $this->_isPrevQueryRead ? $this->_distribute->_getReadLink()->getError() : $this->_distribute->_getLink()->getError();
    }

    /**
     * 释放查询结果
     *
     * @return void
     */
    public function _free()
    {
        $this->_distribute->_getLink()->free();
        if (!$this->_distribute->_isRwSame()) {
            $this->_distribute->_getReadLink()->free();
        }
    }

    /**
     * 关闭连接
     *
     * @return boolean 是否关闭成功
     */
    public function _close()
    {
        try {
            $this->_distribute->_getLink()->close();
            if (!$this->_distribute->_isRwSame()) {
                $this->_distribute->_getReadLink()->close();
            }
            return TRUE;
        } catch (Peas_System_Db_Exception $e) {
            $e->printTraceToLog();
            return FALSE;
        }
    }

    /**
     * SQL安全过滤
     *
     * @param  string $str 需要过滤的字符串
     * @return string
     */
    public function _escapeString($str)
    {
        return $this->_distribute->_getReadLink()->escapeString($str);
    }

    /**
     * 解析SQL语句，用参数替换SQL语句中的参数位
     * 表名格式：##tableName##
     * 参数格式：#paramName:type#
     * #号字符串为\#
     *
     * @param  string $sql       SQL语句
     * @param  mixed  $param     参数
     * @return string 解析后的Sql语句
     */
    public function _parseSql($sql, $param)
    {
        return self::_doParseSql($sql, $param, $this->_prefix, $this->_suffix, $this->_distribute->_getReadLink());
    }



    /**
     * 获取当前缓存管理实例
     *
     * @return Peas_System_Cache_Interface
     */
    public function _getCacheInstance()
    {
        return $this->_cache;
    }

    /**
     * 获取数据库连接管理实例
     *
     * @return Peas_System_Db_Distribute
     */
    public function _getDistributeInstance()
    {
        return $this->_distribute;
    }


    /**
     * 初始化临时查询设置
     */
    private function _selectStatusInit()
    {
        $this->_ifCache   = FALSE;
        $this->_cacheLife = FALSE;
        $this->_page      = FALSE;
        $this->_pageSize  = 10;
    }

    /**
     * 执行查询语句
     *
     * @param  string  $sql         SQL语句
     * @param  mixed   $param       参数，多个参数用形如array('paramName1' => 'value1', 'paramName2' => 'value2')的数组表示
     * @param  boolean $ifGetTop0   是否仅获取一条结果（默认为FALSE）
     * @param  boolean $isGetObject 是否获取对象形式的结果（默认为FALSE），TRUE返回对象形式的结果，FALSE返回数组形式的结果
     * @param  array   $resultMap   $isGetObject为FALSE时有效，键名映射关系, array('字段名'=>'结果键名',...), 不设置表示不做任何改变(即'字段名'和'结果键名'是一样的)
     * @param  string  $className   $isGetObject为TRUE时有效，要实例化的类的名称，如果没有指定，返回stdClass对象
     * @param  array   $classParam  $isGetObject为TRUE时有效，可选的传递给$className对象的构造函数参数数组
     * @param  array   $propertyMap $isGetObject为TRUE时有效，数据库字段名与object属性名映射关系，array('字段名' => '属性名',...)
     * @return mixed 成功返回单个结果或者结果集数组，失败返回FALSE
     */
    private function _selectFactory($sql, $param = array(), $isGetTop0 = FALSE, $isGetObject = FALSE, $resultMap = array(), $className = '', $classParam = array(), $propertyMap = array())
    {
        // 启用了缓存
        if ($this->_ifCache && $this->_cache != NULL) {
            $idKey = $isGetObject ? array($sql, $param, $isGetTop0, $className, $classParam, $propertyMap, $this->_page, $this->_pageSize) : array($sql, $param, $isGetTop0, $resultMap, $this->_page, $this->_pageSize);
            $cacheId = md5($idKey);
            $result = $this->_cache->get($cacheId);
            if ($result !== FALSE) {
                $this->_selectStatusInit();
                return $result;
            }
        }
        $sql = $this->_parseSql($sql, $param);
        if ($isGetTop0) {
            $selectResult = $this->_selectAndCatchException($sql, $isGetObject, $resultMap, $className, $classParam, $propertyMap);
            $result = $selectResult === FALSE ? FALSE : (isset($selectResult[0]) ? $selectResult[0] : NULL);
        } else if ($this->_page !== FALSE) {
            $numRows = $this->_getNumAndCatchException($sql);
            if ($numRows > 0 && $this->_pageSize * ($this->_page - 1) >= $numRows) {
                $this->_page = ceil($numRows / $this->_pageSize);
            }
            $sql .= ' limit ' . ($this->_pageSize * ($this->_page - 1)) . ',' . $this->_pageSize;
            $selectResult = $this->_selectAndCatchException($sql, $isGetObject, $resultMap, $className, $classParam, $propertyMap);
            $result = $selectResult === FALSE ? FALSE : array(
                    'numRows'  => $numRows,
                    'page'     => $this->_page,
                    'pageSize' => $this->_pageSize,
                    'result'   => $selectResult
            );
        } else {
            $selectResult = $this->_selectAndCatchException($sql, $isGetObject, $resultMap, $className, $classParam, $propertyMap);
        }

        // 记录缓存
        if ($this->_ifCache && $this->_cache != NULL) {
            $this->_cache->set($cacheId, $result, $this->_cacheLife);
        }
        $this->_selectStatusInit();
        return $result;
    }

    /**
     * 执行获取结果数量
     *
     * @param  string $sql 已经经过解析的SQL语句
     * @return int|FALSE 结果数量，执行失败返回FALSE
     */
    private function _getNumAndCatchException($sql)
    {
        try {
            $this->_isPrevQueryRead = TRUE;
            return $this->_distribute->_getReadLink()->getNumRows($sql);
        } catch (Peas_System_Db_Exception $e) {
            $e->printTraceToLog();
            return FALSE;
        }
    }

    /**
     * 执行查询
     *
     * @param  string  $sql         已经经过解析的SQL语句
     * @param  boolean $isGetObject 是否获取对象形式的结果（默认为FALSE），TRUE返回对象形式的结果，FALSE返回数组形式的结果
     * @param  array   $resultMap   $isGetObject为FALSE时有效，键名映射关系, array('字段名'=>'结果键名',...), 不设置表示不做任何改变(即'字段名'和'结果键名'是一样的)
     * @param  string  $className   $isGetObject为TRUE时有效，要实例化的类的名称，如果没有指定，返回stdClass对象
     * @param  array   $classParam  $isGetObject为TRUE时有效，可选的传递给$className对象的构造函数参数数组
     * @param  array   $propertyMap $isGetObject为TRUE时有效，数据库字段名与object属性名映射关系，array('字段名' => '属性名',...)
     * @return array|boolean 成功返回结果数组，失败返回FALSE
     */
    private function _selectAndCatchException($sql, $isGetObject = FALSE, $resultMap = array(), $className = '', $classParam = array(), $propertyMap = array())
    {
        $this->_isPrevQueryRead = TRUE;
        try {
            return $isGetObject ? self::_doSelectForObject($this->_distribute->_getReadLink(), $sql, $className, $classParam, $propertyMap) : self::_doSelect($this->_distribute->_getReadLink(), $sql, $resultMap);
        } catch (Peas_System_Db_Exception $e) {
            $e->printTraceToLog();
            return FALSE;
        }
    }




    /**
     * 执行查询语句，获取数组组成的结果集
     *
     * @param  Peas_System_Db_Interface $link 数据库连接
     * @param  string $sql       SQL语句
     * @param  array  $resultMap 键名映射关系, array('字段名'=>'结果键名',...), 不设置表示不做任何改变(即'字段名'和'结果键名'是一样的)
     * @return array 数组形式的结果集数组
     * @throws Peas_System_Db_Exception 204
     */
    private static function _doSelect($link, $sql, $resultMap = array())
    {
        $result = $link->select($sql);
        // 处理映射
        if (!empty($resultMap) && is_array($resultMap)) {
            foreach ($result as $key => $val) {
                if (is_array($val)) {
                    foreach ($resultMap as $mapKey => $mapVal) {
                        if (array_key_exists($mapKey, $val)) {
                            $val[$mapVal] = $val[$mapKey];
                            unset($val[$mapKey]);
                        }
                    }
                    $result[$key] = $val;
                }
            }
        }
        return $result;
    }

    /**
     * 执行查询语句，获取对象组成的结果集
     *
     * @param  Peas_System_Db_Interface $link      数据库连接
     * @param  string $sql         已经解析过的SQL语句
     * @param  string $className   要实例化的类的名称，如果没有指定，返回stdClass对象
     * @param  array  $classParam  可选的传递给$className对象的构造函数参数数组
     * @param  array  $propertyMap 数据库字段名与object属性名映射关系，array('字段名' => '属性名',...)
     * @return array 对象形式的结果集数组
     * @throws Peas_System_Db_Exception 204
     */
    private static function _doSelectForObject($link, $sql, $className = '', $classParam = array(), $propertyMap = array())
    {
        // 处理映射
        if (!empty($propertyMap)) {
            if (empty($className)) {
                $result = $link->selectForObject($sql, $className, $classParam);
                foreach ($result as $item) {
                    foreach ($propertyMap as $mapKey => $mapVal) {
                        if (property_exists($item, $mapKey)) {
                            $item->$mapVal = $item->$mapKey;
                            unset($item->$mapKey);
                        }
                    }
                }
            } else {
                $result = $link->selectForObject($sql);
                foreach ($result as $key => $val) {
                    $resultClassRef = new ReflectionClass($className);
                    $resultClass = $resultClassRef->newInstanceArgs($classParam);
                    foreach ($propertyMap as $mapKey => $mapVal) {
                        if (property_exists($val, $mapKey)) {
                            $resultClass->$mapVal = $val->$mapKey;
                        }
                    }
                    $result[$key] = $resultClass;
                }
            }
            return $result;
        }
        return $link->selectForObject($sql, $className, $classParam);
    }

    /**
     * 解析SQL语句，用参数替换SQL语句中的参数位
     * 表名格式：##tableName##
     * 参数格式：#paramName:type#, type:'int'、'float'、'string'、'real'
     * #号字符串为\#
     *
     * @param  string $sql    SQL语句
     * @param  mixed  $param  参数
     * @param  string $prefix 表前缀
     * @param  string $suffix 表后缀
     * @param  Peas_System_Db_Interface $link 数据库连接
     * @return string 解析后的Sql语句
     */
    private static function _doParseSql($sql, $param, $prefix, $suffix, $link)
    {
        //替换表名中得表前缀和表后缀占位符
        $sql = str_replace('\#', '_PEAS_SYSTEM_DB_WELL_NO_', $sql);
        $sql = preg_replace("/##([^#]*)##/i", $prefix . "\${1}" . $suffix, $sql);

        if (!is_array($param)) {
            $param = is_object($param) ? ((array) $param) : array('value' => $param);
        }
        $matches = array();
        preg_match_all("/#([^#^:]*)(:([^#^:]*))?#/i", $sql, $matches);
        if (!array_key_exists(0, $matches) || count($matches[0]) < 1) {
            return str_replace('_PEAS_SYSTEM_DB_WELL_NO_', '#', $sql);
        }
        $replace = array();
        foreach ($matches[0] as $key => $str) {
            $realType = strtolower($matches[3][$key]);
            $realVal  = array_key_exists($matches[1][$key], $param) ? $param[$matches[1][$key]] : $param['value'];

            if ($realType == 'int' || $realType == 'integer') {
                $replace[$str] = intval($realVal);
            } else if ($realType == 'float' || $realType == 'double') {
                $replace[$str] = floatval($realVal);
            } else if ($realType == 'string') {
                $replace[$str] = '\'' . $link->escapeString($realVal) . '\'';
            } else if ($realType == 'real') {
                $replace[$str] = $realVal;
            } else {
                $replace[$str] = is_string($realVal) ? '\'' . $link->escapeString($realVal) . '\'' : $realVal;
            }
        }
        foreach ($replace as $key => $val) {
            $sql = str_replace($key, $val, $sql);
        }
        return str_replace('_PEAS_SYSTEM_DB_WELL_NO_', '#', $sql);
    }

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