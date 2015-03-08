<?php

use Wechat\WechatServer;

/**
 * 微信基本服务接口测试
 *
 * Class BaseTest
 */
class BaseTest extends PHPUnit_Framework_TestCase {

    /**
     * @var array 微信接口内部测试账号
     */
    private $parameters = array(
        'echostr' => 'random string',
        'signature' => 'sjhdfgaskfsdbnfhsdhfndsf',
        'timestamp' => '123216352532',
        'nonce' => '2124123',
    );

    /**
     * 测试套件初始化
     */
    public function setUp()
    {
        $this->wechat = new WechatServer();
    }

    /**
     * 测试正确的请求参数
     */
    public function testCheckRequestOk()
    {
        $this->assertTrue($this->wechat->checkRequest($this->parameters));
    }

    /**
     * 测试请求参数异常
     * @expectedException InvalidArgumentException
     */
    public function testCheckRequestThrowInvalidParameterException()
    {
        $payload = array_merge($this->parameters, [
           'echostr' => null
        ]);
        $this->wechat->checkRequest($payload);
    }


    /**
     * 测试微信服务器签名验证
     */
    public function testCheckSignature()
    {
        $this->assertFalse($this->wechat->checkSignature($this->parameters));
    }
}