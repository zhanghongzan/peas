<?php
namespace Peas\Kernel\Helper;

use Peas\Crypt\Aes;
use Peas\Config\Configure;

/**
 * AES加密辅助类，可以在配置文件中配置密钥，配置名称：aes.pwd.版本号，其中，默认配置版本号为df即aes.pwd.df
 *
 * @author kevin
 */
class AesHelper
{
    /**
     * cbc模式
     *
     * @var Aes
     */
    private static $_cbc = null;

    /**
     * ecb模式
     *
     * @var Aes
     */
    private static $_ecb = null;


    /**
     * ecb模式加密
     *
     * @param  string $data 待加密数据
     * @param  string $kv   密钥版本号，不设置使用默认密钥
     * @return string
     */
    public static function ecbEncode($data, $kv = null)
    {
        if ($kv !== null) {
            return self::_getDefaultEcb()->encode($data, Configure::get('aes.pwd.' . $kv));
        }
        return self::_getDefaultEcb()->encode($data);
    }

    /**
     * ecb模式解密
     *
     * @param  string $data 待解密数据
     * @param  string $kv   密钥版本号，不设置使用默认密钥
     * @return string
     */
    public static function ecbDecode($data, $kv = null)
    {
        if ($kv !== null) {
            return self::_getDefaultEcb()->decode($data, Configure::get('aes.pwd.' . $kv));
        }
        return self::_getDefaultEcb()->decode($data);
    }


    /**
     * cbc模式加密
     *
     * @param  string $data 待加密数据
     * @param  string $iv   IV
     * @param  string $kv   密钥版本号，不设置使用默认密钥
     * @return string
     */
    public static function cbcEncode($data, $iv, $kv = null)
    {
        if ($kv !== null) {
            return self::_getDefaultCbc()->encode($data, Configure::get('aes.pwd.' . $kv), $iv);
        }
        return self::_getDefaultCbc()->encode($data, null, $iv);
    }

    /**
     * cbc模式解密
     *
     * @param  string $data 待解密数据
     * @param  string $iv   IV
     * @param  string $kv   密钥版本号，不设置使用默认密钥
     * @return string
     */
    public static function cbcDecode($data, $iv, $kv = null)
    {
        if ($kv !== null) {
            return self::_getDefaultCbc()->decode($data, Configure::get('aes.pwd.' . $kv), $iv);
        }
        return self::_getDefaultCbc()->decode($data, null, $iv);
    }


    /**
     * 生成IV
     *
     * @return string
     */
    public static function createIv()
    {
        return substr(md5(microtime() . mt_rand()), mt_rand(0, 15), 16);
    }

    /**
     * 生成带时间戳的IV，6位随机字符串+10位当前时间戳
     *
     * @return string
     */
    public static function createTimeIv()
    {
        return substr(md5(microtime() . mt_rand()), mt_rand(0, 15), 6) . time();
    }


    /**
     * 获取默认cbc模式aes加密类实体
     *
     * @return \Peas\Crypt\Aes
     */
    private static function _getDefaultCbc()
    {
        if (!empty(self::$_cbc)) {
            return self::$_cbc;
        }
        self::$_cbc = new Aes(['mode' => 'cbc', 'defaultKey' => Configure::get('aes.pwd.df')]);
        return self::$_cbc;
    }

    /**
     * 获取默认ecb模式aes加密类实体
     *
     * @return \Peas\Crypt\Aes
     */
    private static function _getDefaultEcb()
    {
        if (!empty(self::$_ecb)) {
            return self::$_ecb;
        }
        self::$_ecb = new Aes(['defaultKey' => Configure::get('aes.pwd.df')]);
        return self::$_ecb;
    }
}
