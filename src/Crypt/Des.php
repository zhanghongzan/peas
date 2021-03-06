<?php
namespace Peas\Crypt;

use Peas\Support\Traits\ConfigTrait;
use Peas\Crypt\Padding\Padding;

/**
 * Peas Framework
 *
 * DES加密类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Des
{
    use ConfigTrait;

    /**
     * 默认配置
     *
     * @var array
     */
    private $_config = [
        'mode'       => 'ecb',          // 加密模式：cbc、cfb、ecb、ofb
        'padding'    => 'PKCS5Padding', // 填充模式：PKCS5Padding、PKCS7Padding、NoPadding、ISO10126Padding
        'base64'     => true,           // 加密结果是否用base64加密
        'defaultKey' => '',             // 默认密钥，加解密时未传入密钥则使用默认
    ];

    /**
     * 参数对照
     *
     * @var array
     */
    private static $_modeInfo = [
        'mode' => [
            'cbc'    => MCRYPT_MODE_CBC,
            'cfb'    => MCRYPT_MODE_CFB,
            'ecb'    => MCRYPT_MODE_ECB,
            'nofb'   => MCRYPT_MODE_NOFB,
            'ofb'    => MCRYPT_MODE_OFB,
            'stream' => MCRYPT_MODE_STREAM,
        ],
        'padding' => [
            'pkcs5padding'    => 'PKCS5Padding',
            'pkcs7padding'    => 'PKCS7Padding',
            'nopadding'       => 'NoPadding',
            'iso10126padding' => 'ISO10126Padding',
        ]
    ];


    /**
     * 最近一次调用createIv方法生成的IV
     *
     * @var string
     */
    public $iv = '';


    /**
     * 构造函数，初始化参数
     *
     * @param array $config 默认值：[<br>
     *     'mode'       => 'ecb',          // 加密模式：cbc、cfb、ecb、ofb<br>
     *     'padding'    => 'PKCS5Padding', // 填充模式：PKCS5Padding、PKCS7Padding、NoPadding、ISO10126Padding<br>
     *     'base64'     => true,           // 加密结果是否用base64加密<br>
     *     'defaultKey' => '',             // 默认密钥，加解密时未传入密钥则使用默认<br>
     * ]
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }


    /**
     * 生成IV
     *
     * @return string IV
     */
    public function createIv()
    {
        $this->iv = substr(md5(microtime() . mt_rand()), mt_rand(0, 23), 8);
        return $this->iv;
    }

    /**
     * 加密
     *
     * @param  string $data  明文，待加密数据
     * @param  string $key   密钥，不设置则使用默认密钥
     * @param  string $iv    IV，如果不设置则自动调用createIv方法生成，可通过$this->iv获取
     * @return string 密文
     */
    public function encode($data, $key = null, $iv = null)
    {
        $padding = self::$_modeInfo['padding'][strtolower($this->getConfig('padding'))];
        $mode    = self::$_modeInfo['mode'][$this->getConfig('mode')];

        $key = empty($key) ? $this->getConfig('key') : $key;
        $iv  = empty($iv)  ? $this->createIv() : $iv;

        $cipherText = mcrypt_encrypt('tripledes', $key, Padding::$padding($data), $mode, $iv);
        return $this->getConfig('base64') ? base64_encode($cipherText) : $cipherText;
    }

    /**
     * 解密
     *
     * @param  string $data  密文，待解密数据
     * @param  string $key   密钥，不设置则使用默认密钥
     * @param  string $iv    IV
     * @return string 明文
     */
    public function decode($data, $key = null, $iv = null)
    {
        $padding = self::$_modeInfo['padding'][strtolower($this->getConfig('padding'))];
        $mode = self::$_modeInfo['mode'][$this->getConfig('mode')];
        $key = empty($key) ? $this->getConfig('key') : $key;

        $result = mcrypt_decrypt('tripledes', $key, $this->getConfig('base64') ? base64_decode($data) : $data, $mode, $iv);
        $paddingName = 'trim' . $padding;
        return Padding::$paddingName(trim($result));
    }
}
