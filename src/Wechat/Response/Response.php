<?php 
/** 
 * Ideas.top 工作室
 *
 * @author Jobslong
 * 2015/1/26
 */

require_once 'WechatTextResponse.php';

// 微信信息响应处理类
class WechatResponse 
{
    /**
     * Puzzle\Configuratio
     * @var $config
     */
    public $config;

    public function __construct()
    {
        $wechatConfig = new WechatConfig();
        $this->config = $wechatConfig->getInstance();
    }

	/**
     * 响应用户消息
     * 
     * @param String $postStr
     * @return Response
     */
	public function response($postStr)
	{
        $postObj = $this->parseXMLMessage($postStr);

        // dispatch message specify by messageTpye             
        switch ($postObj->MsgType)
        {
            case "text":
                $textResponse = new WechatTextResponse();
                $result = $textResponse->receive($postObj);
                break;
            default:
                $result = "unknown msg type: " . $postObj->MsgType;
                break;
        }

        return $result;
	}

    /**
     * 解析XML格式的信息
     * 
     * @param String $postStr
     * @return Array $postObj
     */
    protected function parseXMLMessage($postStr)
    {
        if (!empty($postStr)) {
            $postObj = simplexml_load_string($postStr, 
                'SimpleXMLElement', LIBXML_NOCDATA);
            return $postObj;
        } else {
            echo "";
            exit;
        }
    }

    /**
     * 对信息进行解密
     * 
     * @param String $postStr
     * @return Array $postObj
     */
    public function encryptMessage($postStr, $encrypt_type)
    {
        // todo
        return $postStr;
    }

    /**
     * 对信息进行加密
     * 
     * @param String $postStr
     * @return Array $postObj
     */
    public function decryptMessage($postStr, $decrypt_type)
    {
        return $postStr;
    }
}