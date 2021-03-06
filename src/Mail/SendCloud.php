<?php
namespace Peas\Mail;

use Peas\Support\Traits\ConfigTrait;

/**
 * Peas Framework
 *
 * SendCloud邮件发送类
 *
 * 使用此类需要设置如下配置<br>
 * [<br>
 *     'apiUrl'          => 'https://sendcloud.sohu.com/webapi/mail.send.json', // SendCloud地址<br>
 *     'apiUser'         => '', // SendCloud中配置的发信子账号<br>
 *     'apiKey'          => '', // Sendcloud中分配的API_KEY<br>
 *     'defaultFrom'     => '', // 默认发件邮箱<br>
 *     'defaultFromName' => '', // 默认发件人名称<br>
 * ]
 *
 * @author  Hongzan Zhang <zhanghongzan@163.com>
 * @version $Id$
 */
class SendCloud
{
    use ConfigTrait;


    /**
     * 初始化，设置参数
     *
     * @param array $config 如：[<br>
     *     'apiUrl'          => 'https://sendcloud.sohu.com/webapi/mail.send.json', // SendCloud地址<br>
     *     'apiUser'         => '', // SendCloud中配置的发信子账号<br>
     *     'apiKey'          => '', // Sendcloud中分配的API_KEY<br>
     *     'defaultFrom'     => '', // 默认发件邮箱<br>
     *     'defaultFromName' => '', // 默认发件人名称<br>
     * ]
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Sendcloud邮件发送
     *
     * @param  string $to       收件邮箱
     * @param  string $subject  邮件主题
     * @param  string $html     邮件内容
     * @param  string $from     发件邮箱，默认为空，使用默认配置
     * @param  string $fromName 发件人名称，默认为空，使用默认配置
     * @return string
     */
    public function send($to, $subject, $html, $from = '', $fromName = '')
    {
        if (empty($to) || empty($subject)) {
            return '{"message":"empty"}';
        }

        if (empty($from)) {
            $from = $this->getConfig('defaultFrom');
        }
        if (empty($fromName)) {
            $fromName = $this->getConfig('defaultFromName');
        }
        $param = array (
            'api_user' => $this->getConfig('apiUser'),
            'api_key'  => $this->getConfig('apiKey'),
            'from'     => $from,
            'fromname' => $fromName,
            'to'       => $to,
            'subject'  => $subject,
            'html'     => $html,
        );
        $options = ['http' => ['method'  => 'POST', 'content' => http_build_query($param)]];
        $context = stream_context_create($options);
        return file_get_contents($this->getConfig('apiUrl'), false, $context);
    }
}
