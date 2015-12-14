<?php
use Tivoka\Exception;

class SSAPI {

    private $connection;
    private $notif_connection;
    private $target;
    private $auth;
    private $mode;
    private $access;

    function __construct($host, $port, $auth_token, $auth_group, $timeout = 15, $mode = NULL){

        if(is_null($mode)) $mode = (SSAPI_CONNECTION_NOTIFYS_ENABLE | SSAPI_CONNECTION_ENCRIPTION_DISABLE);
        if(!$host){ throw new Exception\ConnectionException('host is required'); }
        if(!$port){ throw new Exception\ConnectionException('port is required'); }
        if(!$auth_token) { throw new Exception\ConnectionException('auth_token is required'); }
        if(!$auth_group) { throw new Exception\ConnectionException('auth_group is required'); }

//                    $this->target = array('host' => $host, 'port' => $port);
        $this->target = array('host' => $host, 'port' => $port);
        $this->connection = Tivoka\Client::connect($this->target);
        $this->connection->setTimeout($timeout);

        if($mode & SSAPI_CONNECTION_NOTIFYS_ENABLE){
            $this->notif_connection = Tivoka\Client::connect($this->target);
            $this->notif_connection->setTimeout($timeout);
        }

        $this->auth = ['token' => $auth_token, 'group' => $auth_group];
        $this->mode = $mode;

        if(!$this->auth()){
            throw new Exception\ConnectionException('auth: failed');
        }
    }

    private function auth(){
        $request = $this->connection->sendRequest('auth.init', ['_group_id' => $this->auth['group']]);
        if(!$request->isError()){

            $request->result['_key_id'];
            $request2 = $this->connection->sendRequest('auth.vrf', ['check' => (md5($request->result['_key_id'].$this->auth['token']))]);

            if(!$request2->isError()){
                if($request2->result['result']){
                    $this->access = $request2->result['access'];

                    if($this->mode & SSAPI_CONNECTION_NOTIFYS_ENABLE){
                        $salt = time();
                        $this->notif_connection->sendNotification('auth.nrm', ['_group_id' => $this->auth['group'], '_key_id' => $request->result['_key_id'], 'check' => (md5($request->result['_key_id'].$this->auth['token'].$salt)), 'salt' => $salt]);
                    }

                    return true;
                }
            } else { throw new Exception\ConnectionException('auth: cant get answer 2'); }

        } else { throw new Exception\ConnectionException('auth: cant get answer 1'); }

        return false;
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
    public function order_items($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('orders.items', $search, $data, $flags, $options);
    }
    public function items($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('items', $search, $data, $flags, $options);
    }
    public function item_images($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('items.images', $search, $data, $flags, $options);
    }
    public function item_translations($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('items.translations', $search, $data, $flags, $options);
    }
    public function item_categories($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('items.categories', $search, $data, $flags, $options);
    }
    public function users($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('users', $search, $data, $flags, $options);
    }
    public function user_profiles($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('users.profiles', $search, $data, $flags, $options);
    }
    public function groups($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('groups', $search, $data, $flags, $options);
    }
    public function group_rules($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('groups.rules', $search, $data, $flags, $options);
    }
    public function group_domains($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('groups.domains', $search, $data, $flags, $options);
    }
//========================OMNIS================================
    public function omnis_orders($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('omnis.orders', $search, $data, $flags, $options);
    }
    public function omnis_order_items($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('omnis.orders.items', $search, $data, $flags, $options);
    }
    public function omnis_items($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('omnis.items', $search, $data, $flags, $options);
    }
    public function omnis_item_images($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('omnis.items.images', $search, $data, $flags, $options);
    }
    public function omnis_item_translations($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('omnis.items.translations', $search, $data, $flags, $options);
    }
    public function omnis_item_categories($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('omnis.items.categories', $search, $data, $flags, $options);
    }
    public function omnis_users($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('omnis.users', $search, $data, $flags, $options);
    }
    public function omnis_user_profiles($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('omnis.users.profiles', $search, $data, $flags, $options);
    }
    public function omnis_groups($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('omnis.groups', $search, $data, $flags, $options);
    }
    public function omnis_group_rules($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('omnis.groups.rules', $search, $data, $flags, $options);
    }
    public function omnis_group_domains($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('omnis.groups.domains', $search, $data, $flags, $options);
    }

};
?>