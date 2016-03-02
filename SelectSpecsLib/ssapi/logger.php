<?php

class LOGGER {
    private $file_path = null;
    private $email = null;
    private $console_out = false;
    private $echo_out = false;
    private $remote_host = null;

    function __construct($file_path = null, $remote_host=null, $email = null, $console_out = false, $echo_out = false){
        $this->file_path = $file_path;
        $this->email = $email;
        $this->console_out = $console_out;
        $this->echo_out = $echo_out;
        $this->remote_host = $remote_host;
    }

    private function writelog($type, $message, $splitfiles = true){
//        echo "\nMESSAGE: ".$message."\n";
        if(is_array($message)) $message = $this->array_deepout($message);
//        echo "\nMESSAGE2: ".$message."\n";
        $message = strtoupper($type).' ('.date(DATE_RFC2822).'): '.$message."\r\n";

        if($this->echo_out) {
            echo 'LOGGER '.$message;
        }
        if($this->console_out){
            error_log ($message, 0);
        }
        if(!is_null($this->file_path) && $this->file_path){
            if($splitfiles){
                error_log ($message, 3, str_replace(".log", '_'.strtolower($type).".log", $this->file_path));
            } else {
                error_log ($message, 3, $this->file_path);
            }
        }
        if(!is_null($this->remote_host) && $this->remote_host){
            error_log ($message, 2, $this->remote_host);
        }
    }

    private function array_deepout($arr){
        $result = '';
        if(is_array($arr)){
            foreach($arr as $k => $v){
//                echo ' '.$k.': ';
                $result.=' '.$k.': ';
                if(is_array($v)){
//                    echo ' [ ';
                    $result.= ' [ ';
                    $result.= $this->array_deepout($v);
                    $result.=' ], ';
//                    echo ' ] ';
                } else {
                    $result.=' '.$v.', ';
//                    echo ' '.$v.', ';
                }
            }
        } else {
            $result.=' '.$arr.' ';
//            echo ' '.$arr.', ';
        }
//        echo "\n>>RESULT:".$result;
        return $result;
    }

    public function error($message, $splitfiles = true){
        $this->writelog('ERROR', $message, $splitfiles);
    }
    public function info($message, $splitfiles = true){
        $this->writelog('INFO', $message, $splitfiles);
    }
    public function notice($message, $splitfiles = true){
        $this->writelog('NOTICE', $message, $splitfiles);
    }
    public function dubug($message, $splitfiles = true){
        $this->writelog('DEBUG', $message, $splitfiles);
    }
    public function warn($message, $splitfiles = true){
        $this->writelog('WARNING', $message, $splitfiles);
    }
    public function warning($message, $splitfiles = true){
        $this->warn($message, $splitfiles);
    }
}