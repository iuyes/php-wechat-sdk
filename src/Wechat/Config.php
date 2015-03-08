<?php namespace Wechat;
/** 
 * Ideas.top 工作室
 *
 * @author Jobslong
 * 2015/1/23
 */

use Gaufrette\Filesystem as Filesystem;
use Gaufrette\Adapter\Local as Local;

date_default_timezone_set('Asia/Shanghai');

class Config {

	public function getInstance()
	{
		$fileSystem = new Filesystem(new Local(__DIR__ . '/../config'));
        return new \Puzzle\Configuration\Yaml($fileSystem);
	}
}