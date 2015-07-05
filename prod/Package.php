<?php
/**
 * Created by PhpStorm.
 * User: yaoguai
 * Date: 15-7-5
 * Time: 下午2:48
 */

namespace fatty;

//2个字节包长+1个字节打包方式+包体
//包体：['驱动标识','表名','字段名1'=>'值1','字段名2'=>'值2']
//发送的数据最后要加\r\n作为分包的依据
class Package{

    public $error = [];

    const DRIVER_MYSQL = '0';
    const DRIVER_MONGO = '1';

    const PACK_EOF = "\r\n";
    const PACK_EOF_LEN = 2;

    protected $host;
    protected $port;
    protected $bufferDriver;
    protected $packDriver;

    public function init($host,$port,$bufferDriver,$packDriver){
        $this->host = $host;
        $this->port = $port;
        $this->bufferDriver = $bufferDriver;
        $this->packDriver = $packDriver;
    }

    public function addPackage(&$data){
        $body = $this->packDriver->pack($data);
        if(($bodyLen = strlen($body))>65535){
            $this->error[] = 'body is too long';
            return false;
        }
        $bodyLenString = pack('n',$bodyLen);
        $this->bufferDriver->append($bodyLenString);
        $this->bufferDriver->append(PackManager::getId($this->packDriver));
        $this->bufferDriver->append($body);
        return true;
    }

    public static function parsePackage(&$data){
        static $packDriverCache = [];
        $maxLen = strlen($data) - self::PACK_EOF_LEN;
        $point = $number = $success = 0;
        $messageList = $error = [];
        while($point<$maxLen){
            ++$number;
            $bodyLenString = substr($data,$point,2);
            $point += 2;
            if(strlen($bodyLenString)<2){
                $error[] = 'body len string is error';
                break;
            }
            $packId = substr($data,$point,1);
            $point += 1;
            if(strlen($packId)<1){
                $error[] = 'pack id is error';
                break;
            }
            if(isset($packDriverCache[$packId])){
                $packDriver = $packDriverCache[$packId];
            }else{
                $packDriver = PackManager::getPack($packId);
                if(empty($packDriver)){
                    $error[] = 'pack driver is error';
                    break;
                }
                $packDriverCache[$packId] = $packDriver;
            }
            $bodyLenArr = unpack('n',$bodyLenString);
            if(empty($bodyLenArr) || !isset($bodyLenArr[1])){
                $error[] = 'body len is error';
                break;
            }
            $bodyLen = $bodyLenArr[1];
            $body = substr($data,$point,$bodyLen);
            $point += $bodyLen;
            $message = $packDriver->unpack($body);
            if(empty($message)){
                $error[] = 'message unpack error';
                break;
            }
            ++$success;
            $messageList[] = $message;
        }
        return array(
            'ok' => ($number > 0 && $success == $number),
            'number' => $number,
            'success' => $success,
            'messages' => $messageList
        );
    }

    public function sendPackage(){
        $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $con=socket_connect($sock,$this->host,$this->port);
        if(!$con){
            socket_close($sock);
            $this->error[] = 'socket connect error';
            return false;
        }
        $this->bufferDriver->append(self::PACK_EOF);
        $data = $this->bufferDriver->get();
        if(($len = strlen($data))<=self::PACK_EOF_LEN){
            $this->error[] = 'package is empty';
            return -1;
        }
        $writeLen = socket_write($sock,$data,$len);
        socket_shutdown($sock);
        socket_close($sock);
        if($writeLen<=0){
            $this->error[] = 'socket send error';
            return -1;
        }
        $this->bufferDriver->clear();
        return $writeLen;
    }
}