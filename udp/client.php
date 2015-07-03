<?php

//文档：http://www.cnblogs.com/xiaowu/archive/2012/09/18/2690677.html
/**
 * 向服务器中的log表中添加日志数据
 */

$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
$data = ['log','data'=>'this is a log message'];
$data = serialize($data);
$len = strlen($data);
$loop = $originLoop = $argv[1] ? : 10;
$number = $argv[2] ? : 100;
$total = $originLoop*$number;
$errorNumber = 0;
$timeList = [];
$secondUnit = 1000000;
while(--$loop>=0){
    for($i=0;$i<$number;++$i){
        $start = microtime(true);
        $send = socket_sendto($sock, $data, $len, 0, '127.0.0.1', 9905);
        if($send){
            $timeList[$loop][$i] = (microtime(true) - $start)*$secondUnit;
        }else{
            ++$errorNumber;
        }

        //echo "{$timeList[$loop][$i]}\n";
    }
}
socket_close($sock);

echo "\n\n\n\nresult:\nerror:{$errorNumber} loop:{$originLoop} number:{$number}\n";
$loopTimeList = [];
foreach($timeList as $k=>$numberList){
    $loopTimeList[$k] = array_sum($numberList)/$number;
    echo "{$loopTimeList[$k]}\n";
}
echo "finally: ".(array_sum($loopTimeList)/$originLoop),"/{$secondUnit}s\n";


sleep(10);
$sendNumber = $total-$errorNumber;
$totalSecond = array_sum($loopTimeList)/$originLoop;
$result = "send:\nnumber:$total\n";
$result .= "error:$errorNumber\nsuccess:$sendNumber\n";
$result .= "takes:{$totalSecond}/{$secondUnit}s\n";
$result .= "rqs:".($total/$totalSecond*$secondUnit)." n/s\n";
$success = intval(file_get_contents('receive-count.txt'));
$result .= "receive:\nsuccess:$success\n\n";
$result .= "last:".(($sendNumber - $success)/$sendNumber*100)."%\n\n";
file_put_contents('result.txt',$result,FILE_APPEND);

/*
client:
error:0 loop:50 number:10000
finally: 0.18803191184998/100000s
finally: 0.18820786476135/100000s
server:
receive count:28010
receive count:28006
丢包率:94.4%


*/