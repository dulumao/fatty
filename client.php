<?php

//文档：http://www.cnblogs.com/xiaowu/archive/2012/09/18/2690677.html
/**
 * 向服务器中的log表中添加日志数据
 */

$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
$data = ['log','data'=>'this is a log message'];
$data = msgpack_serialize($data);
$len = strlen($data);
$loop = $originLoop = 10;
$number = 100;
$timeList = [];
while(--$loop>=0){
    for($i=0;$i<$number;++$i){
        $start = microtime(true);
        socket_sendto($sock, $msg, $len, 0, '127.0.0.1', 9905);
        $timeList[$loop][$i] = microtime(true) - $start;
        echo "{$timeList[$loop][$i]}\n";
    }
}
socket_close($sock);

echo "result:\nloop:{$originLoop} number:{$number}\n";
$loopTimeList = [];
foreach($timeList as $k=>$numberList){
    $loopTimeList[$k] = array_sum($numberList)/$number;
    echo "{$loopTimeList[$k]}\n";
}
echo "finally: ".(array_sum($loopTimeList)/$originLoop);