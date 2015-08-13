<?php
namespace Peas\Routing;

use Peas\Support\Traits\ConfigStaticTrait;

/**
 * Peas Framework
 *
 * 路由
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 *
 *
 * @example
 * 访问User目录下控制器Login的check方法，参数username：a，password：b<br>
 *
 * MODE_NORMAL :<br>
 * http://localhost/index.php?user/login/check&username=a&password=b<br>
 *
 * MODE_PATHINFO:<br>
 * http://localhost/index.php/user/login/check?username=a&password=b<br>
 *
 * MODE_REWRITE:<br>
 * http://localhost/user/login/check?username=a&password=b<br>
 *
 * MODE_REWRITE_STATIC:<br>
 * http://localhost/user/login/check.html?username=a&password=b<br>
 *
 *
 * 路由规则：[<br>
 *     'user' => 'id:1/name:test',<br>
 *     'url'  => '参数名称1:默认值1/参数名称2:默认值2'<br>
 * ]<br>
 */
class Router
{
    use ConfigStaticTrait;

    /**
     * Url模式：普通模式
     *
     * @var int
     */
    const MODE_NORMAL = 0;

    /**
     * Url模式：PATHINFO模式
     *
     * @var int
     */
    const MODE_PATHINFO = 1;

    /**
     * Url模式：rewrite普通模式
     *
     * @var int
     */
    const MODE_REWRITE = 2;

    /**
     * Url模式：rewrite伪静态模式
     *
     * @var int
     */
    const MODE_REWRITE_STATIC = 3;


    /**
     * 默认配置参数
     *
     * @var array
     */
    private static $_config = [
        'mode'      => 2,      // 0:普通模式，1:pathinfo模式，2:rewrite普通模式，3:rewrite伪静态模式
        'suffix'    => 'html', // rewrite伪静态模式后缀
        'separator' => '/',    // URL默认分隔符
        'rules'     => [],     // 路由规则
    ];


    /**
     * 解析当前访问的URL
     *
     * @return string 解析完成的字符串（不含参数的URL表达式），形式为 group/action/method，需做进一步处理才能得到action地址和method名
     */
    public static function dispatch()
    {
        $urlMode = self::getConfig('mode');
        $rootDir = self::_getRootDir();
        $urlStr = '';

        if ($urlMode == self::MODE_REWRITE) {
            $pathStr = array_key_exists('REDIRECT_URL', $_SERVER) ? self::_strTrim($_SERVER['REDIRECT_URL'], $rootDir) : self::_getPathString('redirect');
            $urlStr = self::_strTrim($pathStr, '/');

        } elseif ($urlMode == self::MODE_NORMAL) {
            list($urlStr) = explode('&', $_SERVER['QUERY_STRING'], 2);

        } elseif ($urlMode == self::MODE_REWRITE_STATIC) {
            $pathStr = array_key_exists('REDIRECT_URL', $_SERVER) ? self::_strTrim($_SERVER['REDIRECT_URL'], $rootDir) : self::_getPathString('redirect');
            $suffix = self::getConfig('suffix');
            $urlStr = self::_strTrim($pathStr, '/', ($suffix == '' ? '' : '.') . $suffix);

        } elseif ($urlMode == self::MODE_PATHINFO) {
            $urlStr = self::_strTrim(array_key_exists('PATH_INFO', $_SERVER) ? $_SERVER['PATH_INFO'] : self::_getPathString(), '/');
        }

        // 将url信息还原为peas标准的url信息（即分隔符为/的url信息）并进行路由解析
        $separator = self::getConfig('separator');
        $result = self::_parseUrl(trim($separator == '/' ? $urlStr : str_replace($separator, '/', $urlStr), '/'));

        if (!empty($result[1]) && is_array($result[1])) {
            $_GET = array_merge($result[1], $_GET);
            reset($_GET);
        }
        return $result[0];
    }


    /**
     * 匹配路由规则，解析URL，得到实际URL规则和原始URL中包含的参数
     *
     * @param  string $urlStr 表示url规则的字符串，格式为aaaa/bbbb/cccc
     * @return array  [string, array]: [实际的url规则, url中包含的参数键值对数组]
     */
    private static function _parseUrl($urlStr)
    {
        $rules = self::getConfig('rules');
        if ($urlStr == '' || empty($rules)) {
            return [$urlStr, []];
        }
        $paramArr = [];
        $checkedStr = '';
        $urlArr = $uncheckedArr = explode('/', $urlStr);

        foreach ($urlArr as $val) {
            $checkedStr .= ($checkedStr == '' ? '' : '/') . $val;
            array_shift($uncheckedArr);
            if (array_key_exists($checkedStr, $rules)) {
                return array($checkedStr, self::_parseByRoute($checkedStr, $uncheckedArr));
            }
        }
        return array($urlStr, []);
    }

    /**
     * 按照路由配置解析URL
     *
     * @param  string $urlStr   url信息
     * @param  array  $paramArr 仅包含参数值的有序数组
     * @return array  url中包含的参数键值对数组
     */
    private static function _parseByRoute($urlStr, $paramArr)
    {
        $rules = self::getConfig('rules');
        $paramConfigArr = explode('/', $rules[$urlStr]);
        $count = count($paramConfigArr);
        $get = [];
        for ($i = 0; $i < $count; $i ++) {
            $paramInfoArr = explode(':', $paramConfigArr[$i]);
            if (array_key_exists($i, $paramArr)) {
                $get[$paramInfoArr[0]] = $paramArr[$i];
            } else if (array_key_exists(1, $paramInfoArr)) {
                $get[$paramInfoArr[0]] = $paramInfoArr[1];
            }
        }
        return $get;
    }


    /**
     * 根据当前URL规则生成URL
     *
     * @param  string $url URL表达式，格式：'[分组/模块/操作]?参数1=值1&参数2=值2...#锚点'
     * @return string 根据当前URL规则生成的URL
     */
    public static function createUrl($url)
    {
        $urlArr   = explode('?', $url, 2);
        $urlStr   = array_key_exists(0, $urlArr) ? $urlArr[0] : '';
        $paramStr = array_key_exists(1, $urlArr) ? $urlArr[1] : '';
        list($urlStr, $paramStr) = self::_createUrlByRoute($urlStr, $paramStr);

        $separator = self::getConfig('separator');
        if ($separator != '/') {
            $urlStr = str_replace('/', $separator, $urlStr);
        }
        $urlMode = self::getConfig('mode');
        $rootDir = self::_getRootDir();
        if ($urlStr == '' && $paramStr == '') {
            return $rootDir;
        } else if ($urlMode == self::MODE_REWRITE) {
            return $rootDir . '/' . $urlStr . ($paramStr == '' ? '' : '?' . $paramStr);
        } else if ($urlMode == self::MODE_NORMAL) {
            return $rootDir . '/' . basename($_SERVER['SCRIPT_NAME']) . '?' . $urlStr . ($paramStr == '' ? '' : '&' . $paramStr);
        } else if ($urlMode == self::MODE_REWRITE_STATIC) {
            $suffix = self::getConfig('suffix');
            return $rootDir . '/' . $urlStr . ($suffix == '' ? '' : '.' . $suffix) . ($paramStr == '' ? '' : '?' . $paramStr);
        } else if ($urlMode == self::MODE_PATHINFO) {
            return $rootDir . '/' . basename($_SERVER['SCRIPT_NAME']) . '/' . $urlStr . ($paramStr == '' ? '' : '?' . $paramStr);
        }
    }

    /**
     * 根据路由设置生成url
     *
     * @param  string $urlStr   原始url路径
     * @param  string $paramStr 原始参数字符串
     * @return array  已经处理过的信息数组[$urlStr, $paramStr]
     */
    private static function _createUrlByRoute($urlStr, $paramStr)
    {
        $rules = self::getConfig('rules');
        $routeStr = isset($rules[$urlStr]) ? $rules[$urlStr] : '';

        // 有参数配置，则按照配置将参数组装到url
        if ($paramStr != '' && $routeStr != '') {
            $paramArr = array();
            $baseParamArr = explode('&', $paramStr);
            foreach ($baseParamArr as $oneParamVal) {
                $oneParamValArr = explode('=', $oneParamVal, 2);
                $paramArr[$oneParamValArr[0]] = isset($oneParamValArr[1]) ? $oneParamValArr[1] : '';
            }

            $paramExistArr = array();
            $baseRouteArr = explode('/', $routeStr);
            foreach ($baseRouteArr as $oneParamRoute) {
                list($key) = explode(':', $oneParamRoute, 2);
                if (array_key_exists($key, $paramArr)) {
                    $urlStr .= '/' . $paramArr[$key];
                    $paramExistArr[$key] = true;
                } else {
                    break;
                }
            }

            $resultParamArr = array();
            foreach ($paramArr as $key => $val) {
                if (!array_key_exists($key, $paramExistArr)) {
                    array_push($resultParamArr, $key . '=' . $val);
                }
            }
            $paramStr = implode('&', $resultParamArr);
        }
        return array($urlStr, $paramStr);
    }


    /**
     * 获取根目录
     *
     * @return string 根目录地址
     */
    private static function _getRootDir()
    {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        return ($scriptDir == '/' || $scriptDir == "\\") ? '' : $scriptDir;
    }

    /**
     * 从REQUEST_URI中获取pathinfo或者redirect的url信息
     *
     * @param  string $pathType 路径类型 ('pathinfo'|'redirect')
     * @return string pathinfo或者redirect url信息
     */
    private static function _getPathString($pathType = 'pathinfo')
    {
        $rootDir = self::_getRootDir();
        $requestUri = $_SERVER['REQUEST_URI'];
        if ($requestUri == $rootDir) {
            return '';
        }
        $removeLeftStr  = ($pathType == 'pathinfo') ? $_SERVER['SCRIPT_NAME'] : $rootDir;
        $removeRightStr = ($_SERVER['QUERY_STRING'] == '' ? '' : '?') . $_SERVER['QUERY_STRING'];
        return self::_strTrim($requestUri, $removeLeftStr, $removeRightStr);
    }

    /**
     * 去除字符串左右两边指定的字符串
     *
     * @param  string $str      要处理的字符串
     * @param  string $lTrimStr 左边去除的字符串
     * @param  string $rTrimStr 右边去除的字符串
     * @return string 处理后的字符串
     */
    private static function _strTrim($str, $lTrimStr = '', $rTrimStr = '')
    {
        if ($lTrimStr != '' && substr($str, 0, strlen($lTrimStr)) == $lTrimStr) {
            $str = substr($str, strlen($lTrimStr));
        }
        if ($rTrimStr != '' && substr($str, - strlen($rTrimStr)) == $rTrimStr) {
            $str = substr($str, 0, - strlen($rTrimStr));
        }
        return $str;
    }
}
