<?php namespace Wechat;
/** 
 * Ideas.top 工作室
 *
 * @author Jobslong
 * 2015/1/23
 */

use Desarrolla2\Cache\Cache as SysCache;
use Desarrolla2\Cache\Adapter\File;

class Cache {

	public function getFileCache()
	{
		$cacheDir = '/tmp';
		$adapter = new File($cacheDir);
    	$adapter->setOption('ttl', 7200);
    	return new SysCache($adapter);
	}
	
}