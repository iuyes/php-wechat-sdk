<?php
/** 
 * Ideas.top 工作室
 *
 * @author Jobslong
 * 2015/1/23
 */

use Desarrolla2\Cache\Cache;
use Desarrolla2\Cache\Adapter\File;

class WechatCache {

	public function getFileCache()
	{
		$cacheDir = '/tmp';
		$adapter = new File($cacheDir);
    	$adapter->setOption('ttl', 7200);
    	return new Cache($adapter);
	}
	
}