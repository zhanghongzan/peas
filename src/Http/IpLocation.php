<?php
namespace Peas\Http;

/**
 * Peas Framework
 *
 * Ip地址类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class IpLocation
{
    /**
     * QQWry.Dat文件指针
     *
     * @var resource
     */
    private $_fp;

    /**
     * 第一条IP记录的偏移地址
     *
     * @var int
     */
    private $_firstip;

    /**
     * 最后一条IP记录的偏移地址
     *
     * @var int
     */
    private $_lastip;

    /**
     * IP记录的总条数（不包含版本信息记录）
     *
     * @var int
     */
    private $_totalip;


    /**
     * 构造函数，打开QQWry.Dat文件并初始化类中的信息
     *
     * @param  string $datFilePath 数据文件地址（QQWry.Dat文件）
     */
    public function __construct($datFilePath)
    {
        $this->_fp = 0;
        if (($this->_fp = fopen($datFilePath, 'rb')) !== false) {
            $this->_firstip = $this->_getlong();
            $this->_lastip  = $this->_getlong();
            $this->_totalip = ($this->_lastip - $this->_firstip) / 7;
        }
    }

    /**
     * 析构函数，用于在页面执行结束后自动关闭打开的文件
     */
    public function __destruct()
    {
        if ($this->_fp) {
            fclose($this->_fp);
        }
        $this->_fp = 0;
    }

    /**
     * 获取访问IP地址
     *
     * @return string
     */
    public static function getClientIp()
    {
        $ip = "unknown";
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } elseif (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } elseif (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
            $ip = getenv("REMOTE_ADDR");
        } elseif (array_key_exists('REMOTE_ADDR', $_SERVER) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
            $ip = $_SERVER ['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * 根据所给IP地址或域名返回所在地区信息
     *
     * @param  string $ipOrDomain IP地址或域名，默认为空表示当前访问IP
     * @return array [
     *     'ip'      => '当前解析的IP',
     *     'country' => '国家信息',
     *     'area'    => '地区信息',
     *     'beginip' => 'IP所在范围的开始地址',
     *     'endip'   => 'IP所在范围的结束地址']
     */
    public function getLocation($ipOrDomain = '')
    {
        return $this->_getLocation(empty($ipOrDomain) ? self::getClientIp() : $ipOrDomain);
    }

    /**
     * 返回读取的长整型数
     *
     * @return int
     */
    private function _getlong()
    {
        $result = unpack('Vlong', fread($this->_fp, 4)); // 将读取的little-endian编码的4个字节转化为长整型数
        return $result ['long'];
    }

    /**
     * 返回读取的3个字节的长整型数
     *
     * @return int
     */
    private function _getlong3()
    {
        $result = unpack('Vlong', fread($this->_fp, 3) . chr(0)); // 将读取的little-endian编码的3个字节转化为长整型数
        return $result ['long'];
    }

    /**
     * 返回压缩后可进行比较的IP地址
     *
     * @param  string $ip
     * @return string
     */
    private function _packip($ip)
    {
        return pack('N', intval(ip2long($ip))); // 将IP地址转化为长整型数，如果在PHP5中，IP地址错误，则返回False，这时intval将Flase转化为整数-1，之后压缩成big-endian编码的字符串
    }

    /**
     * 返回读取的字符串
     *
     * @param  string $data
     * @return string
     */
    private function _getstring($data = "")
    {
        $char = fread($this->_fp, 1);
        while (ord($char) > 0) { // 字符串按照C格式保存，以\0结束
            $data .= $char;      // 将读取的字符连接到给定字符串之后
            $char = fread($this->_fp, 1);
        }
        return $data;
    }

    /**
     * 根据所给IP地址或域名返回所在地区信息
     *
     * @param  string $ip IP地址或域名
     * @return array ['ip'=>'', 'country'=>'', 'area'=>'']
     */
    private function _getLocation($ip)
    {
        if (!$this->_fp || empty($ip)) { // 如果数据文件没有被正确打开，则直接返回空
            return null;
        }
        $location['ip'] = gethostbyname($ip);  // 将输入的域名转化为IP地址
        $ip = $this->_packip($location['ip']); // 将输入的IP地址转化为可比较的IP地址，不合法的IP地址会被转化为255.255.255.255

        // 对分搜索
        $l = 0;                        // 搜索的下边界
        $u = $this->_totalip;          // 搜索的上边界
        $findip = $this->_lastip;      // 如果没有找到就返回最后一条IP记录（QQWry.Dat的版本信息）
        while ($l <= $u) {             // 当上边界小于下边界时，查找失败
            $i = floor(($l + $u) / 2); // 计算近似中间记录
            fseek($this->_fp, $this->_firstip + $i * 7);
            $beginip = strrev(fread($this->_fp, 4)); // 获取中间记录的开始IP地址，strrev函数在这里的作用是将little-endian的压缩IP地址转化为big-endian的格式，以便用于比较，后面相同

            if ($ip < $beginip) { // 用户的IP小于中间记录的开始IP地址时
                $u = $i - 1;      // 将搜索的上边界修改为中间记录减一
            } else {
                fseek($this->_fp, $this->_getlong3());
                $endip = strrev(fread($this->_fp, 4)); // 获取中间记录的结束IP地址
                if ($ip > $endip) {                    // 用户的IP大于中间记录的结束IP地址时
                    $l = $i + 1;                       // 将搜索的下边界修改为中间记录加一
                } else {                               // 用户的IP在中间记录的IP范围内时
                    $findip = $this->_firstip + $i * 7;
                    break;                             // 则表示找到结果，退出循环
                }
            }
        }

        //获取查找到的IP地理位置信息
        fseek($this->_fp, $findip);
        $location['beginip'] = long2ip($this->_getlong()); // 用户IP所在范围的开始地址
        $offset = $this->_getlong3();
        fseek($this->_fp, $offset);
        $location['endip'] = long2ip($this->_getlong());   // 用户IP所在范围的结束地址
        $byte = fread($this->_fp, 1);                      // 标志字节
        switch (ord($byte)) {
            case 1 : // 标志字节为1，表示国家和区域信息都被同时重定向
                $countryOffset = $this->_getlong3();       // 重定向地址
                fseek($this->_fp, $countryOffset);
                $byte = fread($this->_fp, 1);              // 标志字节
                switch (ord($byte)) {
                    case 2 : // 标志字节为2，表示国家信息又被重定向
                        fseek($this->_fp, $this->_getlong3());
                        $location['country'] = $this->_getstring();
                        fseek($this->_fp, $countryOffset + 4);
                        $location['area'] = $this->_getarea();
                        break;
                    default : // 否则，表示国家信息没有被重定向
                        $location['country'] = $this->_getstring($byte);
                        $location['area'] = $this->_getarea();
                        break;
                }
                break;
            case 2 : // 标志字节为2，表示国家信息被重定向
                fseek($this->_fp, $this->_getlong3());
                $location['country'] = $this->_getstring();
                fseek($this->_fp, $offset + 8);
                $location['area'] = $this->_getarea();
                break;
            default : // 否则，表示国家信息没有被重定向
                $location['country'] = $this->_getstring($byte);
                $location['area'] = $this->_getarea();
                break;
        }
        if ($location['country'] == " CZ88.NET") { // CZ88.NET表示没有有效信息
            $location['country'] = "未知";
        }
        if ($location['area'] == " CZ88.NET") {
            $location['area'] = "";
        }
        return $location;
    }

    /**
     * 返回地区信息
     *
     * @return string
     */
    private function _getarea()
    {
        $byte = fread($this->_fp, 1); // 标志字节
        switch (ord($byte)) {
            case 0 : // 没有区域信息
                $area = "";
                break;
            case 1 :
            case 2 : // 标志字节为1或2，表示区域信息被重定向
                fseek($this->_fp, $this->_getlong3());
                $area = $this->_getstring();
                break;
            default : // 否则，表示区域信息没有被重定向
                $area = $this->_getstring($byte);
                break;
        }
        return $area;
    }
}
