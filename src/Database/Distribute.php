<?php
namespace Peas\Database;

use Peas\Database\Driver\DriverInterface;

/**
 * Peas Framework
 *
 * 数据库连接管理
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Distribute
{
    /**
     * 初始化连接ID
     *
     * @var int
     */
    private $_initId = 0;


    /**
     * 数据库连接ID（读写）
     *
     * @var int
     */
    private $_rwLinkId = 0;

    /**
     * 数据库连接实例（读写）
     *
     * @var DriverInterface
     */
    private $_rwLink = null;


    /**
     * 数据库连接ID（读写）
     *
     * @var int
     */
    private $_rLinkId = 0;

    /**
     * 数据库连接实例（只读）
     *
     * @var Peas_System_Db_Interface
     */
    private $_rLink = NULL;


    /**
     * 初始化数据库ID
     *
     * @param int $dbId 数据库ID
     */
    public function __construct($dbId)
    {
        $this->_initId = $dbId;
        $distribute = Peas_System_Application::_getConfig('_db_distribute');
        if (!empty($distribute[1]) && is_array($distribute[1]) && in_array($this->_initId, $distribute[1])) {
            $bigNumArr = array();
            $totalNum = 0;
            foreach ($distribute[1] as $key => $idItem) {
                $totalNum += isset($distribute[2][$key]) ? $distribute[2][$key] : 100;
                $bigNumArr[$key] = $totalNum;
            }
            $rand = mt_rand(1, $totalNum);
            $this->_readLinkId =$distribute[1][0];
            foreach ($bigNumArr as $key => $numItem) {
                if ($rand <= $numItem) {
                    $this->_readLinkId = $distribute[1][$key];
                    break;
                }
            }
            $this->_linkId = $distribute[0] ? $distribute[1][0] : $this->_readLinkId;
        } else {
            $this->_linkId = $this->_readLinkId = $this->_initId;
        }
    }

    /**
     * 读写库是否一致
     *
     * @return boolean
     */
    public function _isRwSame()
    {
    	return $this->_linkId == $this->_readLinkId;
    }

    /**
     * 获取读写连接
     *
     * @return Peas_System_Db_Interface
     */
    public function _getLink()
    {
        if (empty($this->_link)) {
            $this->_link = self::_createLink($this->_linkId);
            if ($this->_linkId == $this->_readLinkId) {
                $this->_readLink = $this->_link;
            }
        }
        return $this->_link;
    }

    /**
     * 获取只读连接
     *
     * @return Peas_System_Db_Interface
     */
    public function _getReadLink()
    {
        if (empty($this->_readLink)) {
            $this->_readLink = self::_createLink($this->_readLinkId);
            if ($this->_readLinkId == $this->_linkId) {
                $this->_link = $this->_readLink;
            }
        }
        return $this->_readLink;
    }


    /**
     * 创建数据库连接
     *
     * @param  int $dbId 数据库ID
     * @return Peas_System_Db_Interface
     * @throws Peas_System_Db_Exception
     */
    private static function _createLink($dbId, array $config)
    {
        $dbType = empty($config['type']) ? 'Mysqli' : $config['type'];
        $className = 'Peas\\Database\\Driver\\' . ucfirst($dbType);
        if (class_exists($className)) {
            return new $className(Peas_System_Application::_getConfig($dbIdStr . '.config'));
        }
        throw new DbException('[DB]不支持' . $dbType . '类型的数据库', 201);
    }
}
