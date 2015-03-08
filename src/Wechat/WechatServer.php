<?php namespace Wechat;
/**
 * Ideas.top 工作室
 *
 * @author Jobslong
 * 2015/1/23
 */

/**
 * 微信服务
 *
 * Class WechatServer
 * @package Wechat
 */
class WechatServer {

    /**
     * 微信TOKEN
     *
     * @var
     */
    public $_token;

    /**
     * 微信消息
     *
     * @var
     */
    private $message;

    /**
     * 用户openId
     *
     * @var
     */
    private static $_from_id;

    /**
     * 公众号openId
     *
     * @var
     */
    private static $_app_id;

    /**
     * 微信服务器请求参数列表
     * echostr => 随机字符串
     * signature 微信加密签名
     * timestamp 时间戳
     * nonce 随机数
     *
     * @var array
     */
    protected $parameters = array('echostr', 'signature', 'timestamp', 'nonce');

    /**
     * GuzzleHttp\Client
     * @var $httpClient
     */
    protected $httpClient;

    /**
     * Puzzle\Configuratio
     * @var $config
     */
    public $config;

    /**
     * Desarrolla2\Cache\Cache
     * @var $cache
     */
    public $cache;

    /**
     * 构造函数
     */
    public function __construct($token = '')
    {
        $wechatConfig = new Config();
        $this->config = $wechatConfig->getInstance();

        if($token)
        {
            $this->_token = $token;
        }
        else
        {
            $this->_token = $this->config->read('wechat/account/token');
        }

        $this->accessDataPushed();
    }

    /**
     * 获取发送用户的Id
     *
     * @return mixed
     */
    public function getFromUserId()
    {
        return self::$_from_id;
    }

    /**
     * 获取接收消息的微信服务号
     *
     * @return mixed
     */
    public function getAppId()
    {
        return self::$_app_id;
    }

    /**
     * 获取消息数据
     *
     * @return array
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 验证参数的正确性
     *
     * @param array $payload
     * @return true | \InvalidArgumentException
     */
    public function checkRequest(array $payload)
    {
        foreach($this->parameters as $parameter)
        {
            if(!isset($payload[$parameter]) || $payload[$parameter] == null
                || $payload[$parameter] == '')
            {
                throw new \InvalidArgumentException;
            }
        }

        return true;
    }

    /**
     * 验证服务器地址的有效性
     * 
     * @param array [timestamp,once,signature]
     * @return Boolean
     */
    public function _checkSignature()
    {
        // 将token、timestamp、nonce三个参数进行字典序排序
        $tmpArr = array($this->_token,
            $_GET['timestamp'], $_GET['nonce']);

        sort($tmpArr, SORT_STRING);

        // 将三个参数字符串拼接成一个字符串进行sha1加密
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        // 开发者获得加密后的字符串可与signature对比，标识该请求来源于微信
        return $tmpStr == $_GET['signature'];
    }

    /**
     * 获取微信信息
     */
    private function accessDataPushed()
    {
        // 调试模式关闭验证
        if ($_GET['debug'] != 'true' && !$this->_checkSignature())
        {
            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
            die('404 Not Found');
        }

        // 响应微信的token验证
        if ($_GET['echostr'])
        {
            echo preg_replace('/[^a-z0-9]/i', '', $_GET['echostr']);
            exit;
        }

        // 获取微信POST数据
        $postdata = isset($GLOBALS["HTTP_RAW_POST_DATA"]) ? $GLOBALS["HTTP_RAW_POST_DATA"]
            : file_get_contents("php://input");
        if ($postdata)
        {
            $postObj = simplexml_load_string($postdata, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->message = $this->_handlePostObj($postObj);
        }
    }

    /**
     * 转换微信消息为数组
     *
     * @param $postObj
     *
     * @return array
     */
    private function _handlePostObj($postObj)
    {
        $MsgType = strtolower((string)$postObj->MsgType);
        $result = array(
            'from'     => self::$_from_id = (string)htmlspecialchars($postObj->FromUserName),
            'to'       => self::$_my_id = (string)htmlspecialchars($postObj->ToUserName),
            'time'     => (int)$postObj->CreateTime,
            // 时间戳
            'datetime' => date('Y-m-d H:i:s', (int)$postObj->CreateTime),
            // timestamp 格式时间
            'type'     => (string)$MsgType
        );

        // 消息ID
        if (property_exists($postObj, 'MsgId'))
        {
            $result['id'] = (int)$postObj->MsgId;
        }

        // 消息类型
        switch ($result['type'])
        {
            case 'text':
                $result['content'] = (string)$postObj->Content; // Content 消息内容
                break;

            case 'location':
                $result['X'] = (float)$postObj->Location_X; // Location_X 地理位置纬度
                $result['Y'] = (float)$postObj->Location_Y; // Location_Y 地理位置经度
                $result['S'] = (float)$postObj->Scale; // Scale 地图缩放大小
                $result['I'] = (string)$postObj->Label; // Label 地理位置信息
                break;

            case 'image':
                $result['url'] = (string)$postObj->PicUrl; // PicUrl 图片链接，开发者可以用HTTP GET获取
                $result['mid'] = (string)$postObj->MediaId; // MediaId 图片消息媒体id，可以调用多媒体文件下载接口拉取数据。
                break;

            case 'video':
                $result['mid']      = (string)$postObj->MediaId; // MediaId 图片消息媒体id，可以调用多媒体文件下载接口拉取数据。
                $result['thumbmid'] = (string)$postObj->ThumbMediaId; // ThumbMediaId 视频消息缩略图的媒体id，可以调用多媒体文件下载接口拉取数据。
                break;

            case 'link':
                $result['title'] = (string)$postObj->Title;
                $result['desc']  = (string)$postObj->Description;
                $result['url']   = (string)$postObj->Url;
                break;

            case 'voice':
                $result['mid']    = (string)$postObj->MediaId;
                $result['format'] = (string)$postObj->Format;
                if (property_exists($postObj, 'Recognition'))
                {
                    $result['txt'] = (string)$postObj->Recognition;
                }
                break;

            case 'event':
                $result['event'] = strtolower((string)$postObj->Event);
                switch ($result['event'])
                {
                    case 'subscribe':
                    case 'scan':
                        if (property_exists($postObj, 'EventKey'))
                        {
                            $result['key']    = str_replace(
                                'qrscene_', '', (string)$postObj->EventKey
                            );
                            $result['ticket'] = (string)$postObj->Ticket;
                        }
                        break;
                    case 'location':
                        $result['la'] = (string)$postObj->Latitude;
                        $result['lo'] = (string)$postObj->Longitude;
                        $result['p']  = (string)$postObj->Precision;
                        break;
                    case 'click':
                        $result['key'] = (string)$postObj->EventKey;
                        break;
                }
        }

        return $result;
    }

    /**
     * 获取文本消息XML
     *
     * @param $txt
     *
     * @return string
     */
    public static function getXml4Txt($txt)
    {
        $xml = '<MsgType><![CDATA[text]]></MsgType>'
            . '<Content><![CDATA[%s]]></Content>';
        return self::_format2xml(sprintf($xml, $txt));
    }

    /**
     * 获取图片消息XML
     *
     * @param $mid
     *
     * @return string
     */
    public static function getXml4ImgByMid($mid)
    {
        $xml = '<MsgType><![CDATA[image]]></MsgType>'
            . '<Image>'
            . '<MediaId><![CDATA[%s]]></MediaId>'
            . '</Image>';
        return self::_format2xml(sprintf($xml, $mid));
    }

    /**
     * 获取音频消息XML
     *
     * @param $mid
     *
     * @return string
     */
    public static function getXml4VoiceByMid($mid)
    {
        $xml = '<MsgType><![CDATA[voice]]></MsgType>'
            . '<Voice>'
            . '<MediaId><![CDATA[%s]]></MediaId>'
            . '</Voice>';
        return self::_format2xml(sprintf($xml, $mid));
    }

    /**
     * 获取视频消息XML
     *
     * @param        $mid
     * @param        $title
     * @param string $desc
     *
     * @return string
     */
    public static function getXml4VideoByMid($mid, $title, $desc = '')
    {
        $desc = '' !== $desc ? $desc : $title;
        $xml  = '<MsgType><![CDATA[video]]></MsgType>'
            . '<Video>'
            . '<MediaId><![CDATA[%s]]></MediaId>'
            . '<Title><![CDATA[%s]]></Title>'
            . '<Description><![CDATA[%s]]></Description>'
            . '</Video>';
        return self::_format2xml(sprintf($xml, $mid, $title, $desc));
    }

    /**
     * 获取音乐消息XML
     *
     * @param        $url
     * @param        $thumbmid
     * @param        $title
     * @param string $desc
     * @param string $hqurl
     *
     * @return string
     */
    public static function getXml4MusicByUrl($url, $thumbmid, $title, $desc = '', $hqurl = '')
    {
        $xml = '<MsgType><![CDATA[music]]></MsgType>'
            . '<Music>'
            . '<Title><![CDATA[%s]]></Title>'
            . '<Description><![CDATA[%s]]></Description>'
            . '<MusicUrl><![CDATA[%s]]></MusicUrl>'
            . '<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>'
            . '<ThumbMediaId><![CDATA[%s]]></ThumbMediaId>'
            . '</Music>';
        return self::_format2xml(sprintf($xml, $title, '' === $desc ? $title : $desc, $url, $hqurl ? $hqurl : $url, $thumbmid));
    }

    /**
     * 获取图文消息XML
     *
     * @param $list
     *
     * @return string
     */
    public static function getXml4RichMsgByArray($list)
    {
        $max      = 10;
        $i        = 0;
        $ii       = count($list);
        $list_xml = '';
        while ($i < $ii && $i < $max)
        {
            $item = $list[$i++];
            $list_xml .=
                sprintf(
                    '<item>'
                    . '<Title><![CDATA[%s]]></Title> '
                    . '<Description><![CDATA[%s]]></Description>'
                    . '<PicUrl><![CDATA[%s]]></PicUrl>'
                    . '<Url><![CDATA[%s]]></Url>'
                    . '</item>', $item['title'], $item['desc'], $item['pic'], $item['url']
                );
        }
        $xml = '<MsgType><![CDATA[news]]></MsgType>'
            . '<ArticleCount>%s</ArticleCount>'
            . '<Articles>%s</Articles>';
        return self::_format2xml(sprintf($xml, $i, $list_xml));
    }

    /**
     * XML基础模板
     *
     * @param $nodes
     *
     * @return string
     */
    private static function _format2xml($nodes)
    {
        $xml = '<xml>'
            . '<ToUserName><![CDATA[%s]]></ToUserName>'
            . '<FromUserName><![CDATA[%s]]></FromUserName>'
            . '<CreateTime>%s</CreateTime>'
            . '%s'
            . '</xml>';
        return sprintf($xml, self::$_from_id, self::$_app_id, time(), $nodes);
    }

}