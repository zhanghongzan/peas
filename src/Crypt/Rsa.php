<?php
namespace Peas\Crypt;

use Peas\Support\Exception;

/**
 * Peas Framework
 *
 * Rsa加密类，使用openssl生成的密钥文件
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class Rsa
{
    /**
     * 加密
     *
     * @param  mixed  $data              待加密数据
     * @param  string $publicKeyFilePath 公钥文件路径
     * @throws Exception
     * @return string 经过base64编码的结果
     */
    public static function encode($data, $publicKeyFilePath) {
        if (openssl_public_encrypt($data, $encodeData, file_get_contents($publicKeyFilePath))) {
            return base64_encode($encodeData);
        }
        throw new Exception('Rsa加密失败');
    }

    /**
     * 解密
     *
     * @param  string $data               待解密数据（经过base64编码过的）
     * @param  string $privateKeyFilePath 私钥文件路径
     * @throws Exception
     * @return mixed  解密结果
     */
    public static function decode($data, $privateKeyFilePath) {
        if (openssl_private_decrypt(base64_decode($data), $decodeData, file_get_contents($privateKeyFilePath))) {
            return $decodeData;
        }
        throw new Exception('Rsa解密失败');
    }
}
