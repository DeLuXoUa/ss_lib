<?php
use Tivoka\Exception;

class SSAPI {

    private $connection;
    private $notif_connection;
    private $target;

    private function auth($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        $this->send('orderitems', $search, $data, $flags, $options);
    }

    function __construct($host, $port, $auth_token, $auth_group, $timeout = 15, $notify_mode = true){

        global $ssapi_api_params;

        if(!$auth_token && isset($ssapi_api_params['auth_token'])){ $auth_token = $ssapi_api_params['auth_token']; }
        if(!$auth_group && isset($ssapi_api_params['auth_group'])){ $auth_group = $ssapi_api_params['auth_group']; }
        if(!$host && isset($ssapi_api_params['host'])){ $host = $ssapi_api_params['host']; }
        if(!$port && isset($ssapi_api_params['port'])){ $port = $ssapi_api_params['port']; }

        if(!$auth_token) { throw new Exception\ConnectionException('auth_token is required'); }
        if(!$auth_group) { throw new Exception\ConnectionException('auth_group is required'); }
        if(!$host) { throw new Exception\ConnectionException('host is required'); }

        if(!$port){
            throw new Exception\ConnectionException('port is required on tcp connection');
        } else {
//                    $this->target = array('host' => $host, 'port' => $port);
            $target = array('host' => $host, 'port' => $port);
            $this->connection = Tivoka\Client::connect($target);
            $this->notif_connection = Tivoka\Client::connect($target);
            $this->connection->setTimeout(5);
            $this->notif_connection->setTimeout(5);
        }

//        if(!$connection_type){ $connection_type = 'http'; }
//        if(!$connection_type && isset($ssapi_api_params['connection_type'])){ $connection_type = $ssapi_api_params['connection_type']; }

/*        switch($connection_type){

            default:
            case 'http': {
                $target = ("http://$host");
                if ($port) { $target .= ":$port"; }

                $this->connection = Tivoka\Client::connect($target);
                $this->connection->setHeader('User-Agent', 'SSAPI/0.1');
                $this->connection->setHeader('Auth_token', $auth_token);
                $this->connection->setHeader('Auth_group', $auth_group);

                break;
            }
            case 'tcp': {
                if(!$port){
                    throw new Exception\ConnectionException('port is required on tcp connection');
                } else {
//                    $this->target = array('host' => $host, 'port' => $port);
                    $target = array('host' => $host, 'port' => $port);
                    $this->connection = Tivoka\Client::connect($target);
                    $this->notif_connection = Tivoka\Client::connect($target);
                    $this->connection->setTimeout(5);
                    $this->notif_connection->setTimeout(5);
                }
                break;
            }
            case 'websocket': {
                $target = ("ws://$host");
                if ($port) { $target .= ":$port"; }
                $this->connection = Tivoka\Client::connect($target);
                break;
            }
        }
*/
    }

    private function message_parser($search, $data, $flags, $options){

        $message = [];
        $message['flags'] = [];

        if(!is_null($flags)) {
            if($flags & SSAPI_NO_WAIT_RESPONSE) { $message['flags'][] = 'noresponse'; }
            elseif($flags & SSAPI_RETURN_RESULT) { $message['flags'][] = 'returnresult'; } //only if we waiting response we can use this flag

            if($flags & SSAPI_FULL_REWRITE) { $message['flags'][] = 'fullrewrite'; }
            if($flags & SSAPI_ONLY_IN_GROUP) { $message['flags'][] = 'ingrouponly'; }
            if($flags & SSAPI_MULTI_QUERY) { $message['flags'][] = 'multi'; }

            if($flags & SSAPI_TASK_FOR_JOB_SERVER) { $message['flags'][] = 'taskforjobserver'; }

        }

        if($search) { $message['search'] = $search; }
        if($data) { $message['data'] = $data; }
        if($options) { $message['options'] = $options; }

        return $message;

    }

    private function method_parser($method, $search = NULL, $data = NULL, $flags = NULL, $options = NULL) {
        if(!is_null($search) && !is_null($data)){
            if(!is_null($flags) && $flags & SSAPI_CREATE_IF_NOT_EXIST) {
                $method .= 'update';
            } else {
                $method .= 'replace';
            }
        } elseif(!is_null($data)) {
            $method .= 'add';
        } elseif(!is_null($search)) {
            if(!is_null($flags) && $flags & SSAPI_DELETE_DOCUMENT) {
                $method .= 'delete';
            } else {
                $method .= 'get';
            }
        }

        return $method;
    }

    public function message_sender($method, $message){
        if(isset($message['flags']) && in_array('noresponse', $message['flags'])){
            $this->notif_connection->sendNotification($method, $message);
            return true;
        } else {
//            $request = Tivoka\Client::connect($this->target)->sendRequest($method, $message);
            $request = $this->connection->sendRequest($method, $message);
            if($request->isError()){
                return [
                    'result' => false,
                    'error' => $request->error,
                    'errorData' => $request->errorData,
                    'reason' => $request->errorMessage
                ];
            } else {
                return $request->result;
            }
        }
    }

    private function send($method, $search = NULL, $data = NULL, $flags = NULL, $options = NULL) {
        return $this->message_sender(
            $this->method_parser($method, $search, $data, $flags, $options),
            $this->message_parser($search, $data, $flags, $options)
        );
    }

//===========================================================================================
//=======================   P U B L I C   F U N C T I O N S   ===============================
//===========================================================================================

    public function orders($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('orders', $search, $data, $flags, $options);
    }
    public function items($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('items', $search, $data, $flags, $options);
    }
    public function order_items($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('orderitems', $search, $data, $flags, $options);
    }

};
?>