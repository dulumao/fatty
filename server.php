<?php
/**
 * Created by PhpStorm.
 * User: yaoguai
 * Date: 15-7-5
 * Time: 下午4:15
 */

$thinkPhpLoad['start'] = microtime(true);
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
define('APP_DEBUG',false);
define('RUNTIME_PATH',__DIR__.'/');
define('APP_PATH',__DIR__.'/Application/');
require __DIR__.'/ThinkPHP/ThinkPHP.php';
C(load_config(__DIR__.'/config.php'));
$thinkPhpLoad['end'] = microtime(true);
echo "thinkPHP load(s):",$thinkPhpLoad['end'] - $thinkPhpLoad['start'],"\n";

require __DIR__.'/bootstrap.php';
(new SwooleServer(C('SERVER_HOST'),C('SERVER_PORT'),C('SERVER_CONFIG'),C('SERVER_BUFFER_CLASS')))->start();