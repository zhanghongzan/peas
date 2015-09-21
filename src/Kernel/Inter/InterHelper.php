<?php
namespace Peas\Kernel\Inter;

use Peas\Config\Configure;

/**
 * Peas Framework
 *
 * 接口类控制器基类
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class InterHelper
{
    /**
     * 存储结果编码
     *
     * @var array
     */
    private static $_codeMap = null;

    /**
     * 初始化结果编码
     */
    private static function _init()
    {
        if (self::$_codeMap === null) {
            self::$_codeMap = include _PATH . '/resources/inter/codeMap.php';
        }
    }


    /**
     * 获取成功状态InterCode
     *
     * @param int $code
     * @return \Peas\Kernel\Inter\InterCode
     */
    public static function getSuccessCode($code = 10000)
    {
        self::_init();
        $codeDesc = isset(self::$_codeMap['success'][$code]) ? self::$_codeMap['success'][$code] : null;
        return new InterCode(InterCode::STATUS_SUCCESS, $code, empty($codeDesc) ? '' : $codeDesc);
    }

    /**
     * 获取失败状态InterCode
     *
     * @param int $code
     * @return \Peas\Kernel\Inter\InterCode
     */
    public static function getFailureCode($code = 10000)
    {
        self::_init();
        $codeDesc = isset(self::$_codeMap['failure'][$code]) ? self::$_codeMap['failure'][$code] : null;
        return new InterCode(InterCode::STATUS_FAILURE, $code, empty($codeDesc) ? '' : $codeDesc);
    }


    /**
     * 输出响应字符串
     *
     * @param string $text
     */
    public static function printText($text)
    {
        header('Content-Type: text/html; charset=' . Configure::get('_default.charset'));
        $callback = $_REQUEST['callback'];
        echo empty($callback) ? $text : ($callback . '(' . $text . ');');
    }

    /**
     * 输出响应Code，{"status" : "", "code" : "", "desc" : "", "data" : {}}
     *
     * @param InterCode $interCode
     */
    public static function printCode(InterCode $interCode)
    {
        self::printText(json_encode($interCode));
    }


    /**
     * 输出成功信息响应Code，直接从code配置中获取
     *
     * @param int   $code
     * @param array $data
     */
    public static function success($code, array $data = [])
    {
        $interCode = self::getSuccessCode($code);
        $interCode->data = $data;
        self::printCode($interCode);
    }

    /**
     * 输出失败信息响应Code，直接从code配置中获取
     *
     * @param int   $code
     * @param array $data
     */
    public static function failure($code, array $data = [])
    {
        $interCode = self::getFailureCode($code);
        $interCode->data = $data;
        self::printCode($interCode);
    }

    /**
     * 获取AES加密参数
     * 参数中需包含：key（AES加密IV），data（AES加密的数据），v（密钥版本号，可选）
     *
     * @return
     * @throws IOException
     */
    public static function getParamData()
    {
        $key = $_REQUEST['key'];
        $aesData = $_REQUEST['data'];
        $kv = $_REQUEST['kv'];

        if (empty($key) || empty($aesData)) {
            return null;
        }

//         String aesDataStr = this.getString("data", null);
//         String kv = this.getString("v", null);
//         try {
//             if (aesDataStr == null || key == null) {
//                 return null;
//             }
//             String dataStr = AES.decrypt(aesDataStr, ConfigCenter.get((kv == null) ? "AES.passowrd" : "AES.passowrd." + kv), key);
//             JSONObject.toJSON(dataStr);
//             return JSON.parseObject(dataStr);
//         } catch (Exception e) {
//             e.printStackTrace();
//             return null;
//         }
    }
}
