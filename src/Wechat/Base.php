<?php namespace Wechat\
/** 
 * Ideas.top 工作室
 *
 * @author Jobslong
 * 2015/1/23
 */

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Post\PostFile;

class Base {

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

    public function __construct()
    {
        $this->httpClient = new HttpClient;
        $wechatConfig = new Config();
        $this->config = $wechatConfig->getInstance();
        $wechatCache = new Cache();
        $this->cache = $wechatCache->getFileCache();
    }

    public function checkRequest(Array $payload)
    {
        foreach ($payload as $key => $value) 
        {
            if( $value == NULL )
            {
                echo "缺少请求参数错误![" . $key . " 不能为空]";
                exit;
            }
        }
    }

    /**
     * 验证服务器地址的有效性
     * 
     * @param Array [timestamp,once,signature]
     * @return Boolean
     */
    public function checkSignature(Array $paramters)
    {
        // 将token、timestamp、nonce三个参数进行字典序排序
        $tmpArr = array($this->config->read('wechat/base/token'), 
            $paramters['timestamp'], $paramters['nonce']);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        // 将三个参数字符串拼接成一个字符串进行sha1加密
        $tmpStr = sha1($tmpStr);
        // 开发者获得加密后的字符串可与signature对比，标识该请求来源于微信
        return $tmpStr == $paramters['signature'] ? true : false;
    }

    /**
     * 获得access_token
     *
     * @param String $grant_type | default 'client_credential'
     * @return String
     */
    protected function getAccessToken($grant_type = 'client_credential')
    {
        if( !$this->cache->has('access_token') )
        {
            $request = $this->createRequest('access_token');
            $query = $request->getQuery();
            $query->set('grant_type', $grant_type);
            $query->set('appid', $this->config->read('wechat/base/appid'));
            $query->set('secret', $this->config->read('wechat/base/appsecret'));
            $response = $this->httpClient->send($request)->json();

            $this->checkResponse($response);

            // cache access_token
            $this->cache->set('access_token', 
                $response['access_token'], $response['expires_in']);
            return $response['access_token'];
        } else {
            return $this->cache->get('access_token');
        }
    }

    /**
     * 获得微信服务器IP列表
     *
     * @return Array IP list
     */
    public function getCallbackIPs()
    {
        $request = $this->createRequest('callback_ip');
        $query = $this->getAuthQuery($request);
        $response = $this->httpClient->send($request)->json();

        $this->checkResponse($response);

        return $response['ip_list'];
    }

    /**
     * 上传多媒体文件
     *
     * 上传的多媒体文件有格式和大小限制，如下：
     * 图片（image）: 1M，支持JPG格式
     * 语音（voice）：2M，播放长度不超过60s，支持AMR\MP3\SPEEX格式
     * 视频（video）：10MB，支持MP4格式
     * 缩略图（thumb）：64KB，支持JPG格式
     * 媒体文件在后台保存时间为3天，即3天后media_id失效
     * @param File $media
     * @param String $type:image|voice|video|thumb
     * @return Response
     */
    public function uploadMedia($media, $type)
    {
        $request = $this->createRequest('upload_media');
        $query = $this->getAuthQuery($request);
        $query->set('type', $type);

        $body = $request->getBody();
        $body->addFile(new PostFile('test', fopen($media, 'r')));
        $response = $this->httpClient->send($request);
        return $response->json();
    }

    /**
     * 下载多媒体文件
     *
     * @param String $media_id
     * @return Response
     */
    public function downloadMedia($media_id)
    {
        $request = $this->createRequest('download_media');
        $query = $this->getAuthQuery($request);
        $query->set('media_id', $media_id);
        return $this->httpClient->send($request)->getBody();
    }

    /**
     * 创建HTTP请求
     * 
     * @param String $api_name 
     * @return Request $request
     */
    protected function createRequest($api_name)
    {
        $scheme = $this->config->read('wechat/api/' . $api_name . '/scheme');
        $method = $this->config->read('wechat/api/' . $api_name . '/method');
        $url = $this->config->read('wechat/api/' . $api_name . '/url');
        $request = $this->httpClient->createRequest($method, $url);
        $request->setScheme($scheme);
        return $request;
    }

    /**
     * 获得已经加入access_token的请求参数对象
     * 
     * @param Resuest $request
     * @return Query $query
     */
    protected function getAuthQuery($request)
    {
        $query = $request->getQuery();
        $query->set('access_token', $this->getAccessToken());
        return $query;
    }

    /**
     * check response(if error throw exception)
     */
    protected function checkResponse($response)
    {
        if( isset($response['errcode']) )
        {
            throw new Exception($response['errcode'] 
                . ':' . $response['errmsg']);
        }
    }
}