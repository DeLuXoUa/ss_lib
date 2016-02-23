<?php
use Tivoka\Exception;

class SSAPI {

    private $connection;
    private $notif_connection;
    private $target;
    private $auth;
    private $mode;
    private $access;
    private $last_error_data = null; //last error
    private $errors_log = array();
    private $last_ex_time = null; //execution time (profiler timer)
    private $ex_times_log = array();
    private $client_id = null;
    private $protocol_version = 2;

    function __construct($host, $port, $auth_key_id, $auth_token, $auth_group_id, $custom_client_id, $timeout = 15, $mode = NULL){

        if(is_null($mode)) $mode = (SSAPI_CONNECTION_NOTIFYS_ENABLE | SSAPI_CONNECTION_ENCRIPTION_DISABLE);
        if(!$host){ throw new Exception\ConnectionException('host is required'); }
        if(!$port){ throw new Exception\ConnectionException('port is required'); }
        if(!$auth_key_id) { throw new Exception\ConnectionException('auth_key_id is required'); }
        if(!$auth_token) { throw new Exception\ConnectionException('auth_token is required'); }
        if(!$auth_group_id) { throw new Exception\ConnectionException('auth_group is required'); }


//                    $this->target = array('host' => $host, 'port' => $port);
        $this->target = array('host' => $host, 'port' => $port);
        $this->connection = Tivoka\Client::connect($this->target);
        $this->connection->setTimeout($timeout);

        if($mode & SSAPI_CONNECTION_NOTIFYS_ENABLE){
            $this->notif_connection = Tivoka\Client::connect($this->target);
            $this->notif_connection->setTimeout($timeout);
        }

        if(!is_null($custom_client_id)) { $this->client_id = $custom_client_id; }
        else { $this->client_id = md5($this->auth['_key_id'].$this->auth['_group_id']); }

        $this->auth = array('_key_id' => $auth_key_id, 'token' => $auth_token, '_group_id' => $auth_group_id, 'client_id' => $this->client_id);
        $this->mode = $mode;

        if(!$this->auth()){
            throw new Exception\ConnectionException('auth: failed');
        }
    }

    private function auth(){
        $request = $this->connection->sendRequest('auth.init', [
            '_key_id' => $this->auth['_key_id'],
            '_group_id' => $this->auth['_group_id'],
            'client_id' => $this->client_id
        ]);

        if(!$request->isError()){

            $request2 = $this->connection->sendRequest('auth.vrf', [
                'token' => $this->auth['token']
            ]);

            if(!$request2->isError()){
//                print_r($request2->result);
                if($request2->result){
//                    $this->access = $request2->result['access'];
                    if($this->mode & SSAPI_CONNECTION_NOTIFYS_ENABLE){
                        $this->notif_connection->sendNotification('auth.nrm', [
                            '_key_id' => $this->auth['_key_id'],
                            '_group_id' => $this->auth['_group_id'],
                            'token' => $this->auth['token'],
                            'client_id' => $this->client_id
                        ]);
                    }

                    return true;
                }
            } else { throw new Exception\ConnectionException('auth: cant get answer 2'); }

        } else { throw new Exception\ConnectionException('auth: cant get answer 1'); }

        return false;
    }

    private function domain_id_2_group_id($alt_id){
        $alt_id = (int)$alt_id;
        $result =[];
        foreach($GROUP_ALT_ID as $k => $v){
            if($alt_id == $v) $result[]=$k;
        }
        if(count($result)) return $result;
        else return false;
    }

    private function group_id_2_domain_id($gid){
        if(isset($GROUP_ALT_ID[$gid])) return $GROUP_ALT_ID[$gid];
        else return false;
    }

    private function web_json_encode($data){

        if(isset($data['rrp'])) $result['rr_price'] = $data['rrp'];
        if(isset($data['price_old'])) $result['price_old'] = $data['price_old'];
        if(isset($data['price'])) $result['price'] = $data['price'];

        //==> __service
        $result['__service'] = [];
        $result['__service']['client_id'] = $this->client_id;
        $result['__service']['_key_id'] = $this->auth['_key_id'];
        $result['__service']['_group_id'] = $this->auth['_group_id'];

        //==> group_prices
        if(isset($data["prices_domain"])) {
            foreach($data["prices_domain"] as $k => $v) {
                $gp = $this->domain_id_2_group_id($k);
                foreach($gp as $gid){ $result['group_prices'][$gid] = $v; }
            }
        }

        //==> specifications
        if(isset($data['base_curve'])) $result['specifications']['base_curve'] = $data['base_curve'];
        if(isset($data['sph_min'])) $result['specifications']['sph_min'] = $data['sph_min'];
        if(isset($data['sph_max'])) $result['specifications']['sph_max'] = $data['sph_max'];
        if(isset($data['sph_step'])) $result['specifications']['sph_step'] = $data['sph_step'];
        if(isset($data['cyl'])) $result['specifications']['cyl'] = $data['cyl'];
        if(isset($data['axis'])) $result['specifications']['axis'] = $data['axis'];
        if(isset($data['multifocaladd'])) $result['specifications']['multifocaladd'] = $data['multifocaladd'];
        if(isset($data['warranty_period'])) $result['specifications']['warranty_period'] = $data['warranty_period'];
        if(isset($data['pd'])) $result['specifications']['pd'] = $data['pd'];
        if(isset($data['height'])) $result['specifications']['height'] = $data['height'];
        if(isset($data['width'])) $result['specifications']['width'] = $data['width'];
        if(isset($data['depth'])) $result['specifications']['depth'] = $data['depth'];
        if(isset($data['weight'])) $result['specifications']['weight'] = $data['weight'];
        if(isset($data['frame_sizes'])) $result['specifications']['frame_sizes'] = $data['frame_sizes'];

        //==> stock
        if(isset($data['is_out_of_stock'])) $result['stock']['is_out_of_stock'] = $data['is_out_of_stock'];
        if(isset($data['featured'])) $result['stock']['featured'] = $data['featured'];
        if(isset($data['discontinued'])) $result['stock']['discontinued'] = $data['discontinued'];


        if(isset($data['colours'])) $result['colours'] = $data['colours'];
        if(isset($data['description'])) $result['description'] = $data['description'];
        if(isset($data['option_best_status'])) $result['status'] = $data['option_best_status'];
        if(isset($data['model_name'])) $result['model_name'] = $data['model_name'];
        if(isset($data['supplier_name'])) $result['supplier_name'] = $data['supplier_name'];
        if(isset($data['desigenr_name'])) $result['designer_name'] = $data['desigenr_name'];
        if(isset($data['brand_name'])) $result['brand_name'] = $data['brand_name'];
        if(isset($data['categories'])) $result['categories'] = $data['categories'];
        if(isset($data['tab'])) $result['main_categories']=$data['tab'];
        if(isset($data['item_id'])) $result['item_id']=$data['item_id'];
        if(isset($data['option_id'])) $result['option_id']=$data['option_id'];

        //==> migration

        if(isset($data['item_added'])) $result['migration']['item_added'] = $data['item_added'];
        if(isset($data['supp_name'])) $result['migration']['supp_name'] = $data['supp_name'];
        if(isset($data['no_large_image'])) $result['migration']['no_large_image'] = $data['no_large_image'];
        if(isset($data['no_option_images'])) $result['migration']['no_option_images'] = $data['no_option_images'];
        if(isset($data['product_information'])) $result['migration']['product_information'] = $data['product_information'];
        if(isset($data['is_modified'])) $result['migration']['is_modified'] = $data['is_modified'];
        if(isset($data['item_info'])) $result['migration']['item_info'] = $data['item_info'];
        if(isset($data['designer_id'])) $result['migration']['designer_id'] = $data['designer_id'];
        if(isset($data['default_option_order'])) $result['migration']['default_option_order'] = $data['default_option_order'];
        if(isset($data['tab_id'])) $result['migration']['tab_id'] = $data['tab_id'];
        if(isset($data['experiment_key'])) $result['migration']['experiment_key'] = $data['experiment_key'];

        //==> options
        if(isset($data['option_description'])) $result['options']['option_description'] = $data['option_description'];
        if(isset($data['option_name'])) $result['options']['option_name'] = $data['option_name'];
        if(isset($data['option_order'])) $result['options']['option_order'] = $data['option_order'];

        return $result;
    }

    private function web_json_decode($data){
        if(isset($data["rr_price"])) $result["rrp"] = (double)$data["rr_price"];
        if(isset($data["price"])) $result["price"] = (double)$data["price"];

        if(isset($data["group_prices"])){
            foreach($data["group_prices"] as $k => $v) {
                $result["prices_domain"][$this->group_id_2_domain_id($k)] = $v;
            }
        }

        foreach($data['specifications'] as $k => $v){ $result[$k] = $v; }
        foreach($data['options'] as $k => $v){ $result[$k] = $v; }
        foreach($data['stock'] as $k => $v){ $result[$k] = $v; }
        foreach($data['migration'] as $k => $v){ $result[$k] = $v; }

        if(isset($data['colours'])) $result['colours'] = $data['colours'];
        if(isset($data['description'])) $result['description'] = $data['description'];
        if(isset($data['status'])) $result['option_best_status'] = $data['status'];
        if(isset($data['model_name'])) $result['model_name'] = $data['model_name'];
        if(isset($data['supplier_name'])) $result['supplier_name'] = $data['supplier_name'];
        if(isset($data['desigenr_name'])) $result['designer_name'] = $data['desigenr_name'];
        if(isset($data['brand_name'])) $result['brand_name'] = $data['brand_name'];
        if(isset($data['categories'])) $result['categories'] = $data['categories'];
        if(isset($data['main_categories'])) $result['tab']=$data['main_categories'];
        if(isset($data['item_id'])) $result['item_id']=$data['item_id'];
        if(isset($data['option_id'])) $result['option_id']=$data['option_id'];

        return $result;
    }

    private function message_parser($search, $data, $flags, $options){

        $message = array();
        $message['flags'] = array();

        if(!is_null($flags)) {
            if($flags & SSAPI_NO_WAIT_RESPONSE) { $message['flags'][] = 'noresponse'; }
            elseif($flags & SSAPI_RETURN_RESULT) { $message['flags'][] = 'returnresult'; } //only if we waiting response we can use this flag

            if($flags & SSAPI_CREATE_IF_NOT_EXIST) { $message['flags'][] = 'createifnotexist'; }
            if($flags & SSAPI_FULL_REWRITE) { $message['flags'][] = 'fullrewrite'; }
            if($flags & SSAPI_ONLY_IN_GROUP) { $message['flags'][] = 'ingrouponly'; }
            if($flags & SSAPI_MULTI_QUERY) { $message['flags'][] = 'multi'; }
            if($flags & SSAPI_FORCEADD) { $message['flags'][] = 'forceadd'; }

            if($flags & SSAPI_TASK_FOR_JOB_SERVER) { $message['flags'][] = 'taskforjobserver'; }
        }

        if($search) { $message['search'] = $search; }
        if($data) { $message['data'] = $data; }
        $options['protocol_version'] = 2;
        $options['client_id'] = $this->client_id;
        $options['protocol_version'] = $this->protocol_version;
        $message['options'] = $options;

        return $message;
    }

    private function method_parser($method, $search = NULL, $data = NULL, $flags = NULL, $options = NULL) {
        if(!is_null($search) && !is_null($data)){
            $method .= '.update';
        } elseif(!is_null($data)) {
            $method .= '.add';
        } elseif(!is_null($search)) {
            if(!is_null($flags) && $flags & SSAPI_DELETE_DOCUMENT) {
                $method .= '.delete';
            } else {
                $method .= '.get';
            }
        }

        return $method;
    }
    
    public function getLastError(){
        if(is_null($this->last_error_data)){
            return false;
        } else {
            return $this->last_error_data;
        }
    }

    public function getAllErrors(){
        if(!count($this->errors_log)){
            return false;
        } else {
            return $this->errors_log;
        }
    }

    public function getLastTime($round = 3){
        if(is_null($this->last_ex_time)){
            return false;
        } else {
            if($round === false){
                return $this->last_ex_time;
            } else {
                return round($this->last_ex_time, $round);
            }
        }
    }

    public function getAllTimes($round = 3){
        if(!count($this->ex_times_log)){
            return false;
        } else {
            if($round === false){
                return $this->ex_times_log;
            } else {
                $result = array();
                foreach($this->ex_times_log as $t) {
                    $result[] = round($t, $round);
                }
                return $result;
            }
        }
    }

    public function getTotalTime($round = 3){
        if(!count($this->ex_times_log)){
            return false;
        } else {
            $result = 0;
            foreach($this->ex_times_log as $t) {
                $result+=$t;
            }
            if($round === false){
                return $result;
            } else {
                return round($result, $round);
            }
        }
    }
    public function message_sender($method, $message){
        $ex_time = microtime(true);
        if(isset($message['flags']) && in_array('noresponse', $message['flags'])){
            $this->notif_connection->sendNotification($method, $message);
            $ex_time = microtime(true) - $ex_time;
            $this->last_ex_time = $ex_time;
            $this->ex_times_log[] = $ex_time;
            return true;
        } else {
//            $request = Tivoka\Client::connect($this->target)->sendRequest($method, $message);
            $request = $this->connection->sendRequest($method, $message);
            $ex_time = microtime(true) - $ex_time;
            $this->last_ex_time = $ex_time;
            $this->ex_times_log[] = $ex_time;

            if($request->isError()){
                $this->last_error_data = array(
                    'result' => false,
                    'error' => $request->error,
                    'errorData' => $request->errorData,
                    'reason' => $request->errorMessage,
                    'params' => ['method'=>$method]
                );
                foreach($message as $key=>$value){
                    $this->last_error_data['params'][$key] = $value;
                }

                $this->errors_log[] = $this->last_error_data;
                return false;
            } else {
                $this->last_error_data = null;
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

    private function last_updated($type, $from_date, $to_date, $flags = NULL, $options = NULL){
        $search = array();
        $search['__service.client_id']['$ne'] = $this->client_id;

        if(!is_null($to_date)) {
            $search['__service.updated']['$lte'] =  $to_date;
        }
        if(!is_null($from_date)) {
            $search['__service.updated']['$gt'] = $from_date;
        }
/*
         $search = ['q' => [
            ['__service.client_id' => ['$ne' => $this->client_id] ],
            ['__service.updated' => [['$gt' => $from_date], ['$lte' => $to_date] ]]
        ]];
*/
        return $this->send($type, $search, NULL, $flags, $options);
    }

//===========================================================================================
//=======================   P U B L I C   F U N C T I O N S   ===============================
// *_last_updated - is function for search updates creates by another client
//===========================================================================================

    public function orders($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('orders', $search, $data, $flags, $options);
    }
    public function orders_last_updated($from_date, $to_date, $flags = NULL, $options = NULL){
        return $this->last_updated('orders', $from_date, $to_date, $flags, $options);
    }
    public function order_items($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('orders.items', $search, $data, $flags, $options);
    }

    public function items_last_updated($from_date, $to_date, $flags = NULL, $options = NULL){
        return $this->last_updated('items', $from_date, $to_date, $flags, $options);
    }
    public function items($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        if($data && !is_null($flags) && ($flags & SSAPI_CONVERTER_WEB))
        {
            if(!is_null($flags) && ($flags & SSAPI_MULTI_QUERY)) {
                foreach($data as $k => $v){
                    $data[$k] = $this->web_json_encode($v);
                }
            } else {
                $data = $this->web_json_encode($data);
            }
        }

        $result = $this->send('items', $search, $data, $flags, $options);
        if($search && $result) {
            foreach($result as $k => $v){
                $result[$k] = $this->web_json_decode($v);
            }
        }
        return $result;
    }

    public function item_images($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('items.images', $search, $data, $flags, $options);
    }
    public function items_translations_last_updated($from_date, $to_date, $flags = NULL, $options = NULL){
        return $this->last_updated('items.translations', $from_date, $to_date, $flags, $options);
    }
    public function item_translations($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('items.translations', $search, $data, $flags, $options);
    }
    public function item_categories_last_updated($from_date, $to_date, $flags = NULL, $options = NULL){
        return $this->last_updated('item.categories', $from_date, $to_date, $flags, $options);
    }
    public function item_categories($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('items.categories', $search, $data, $flags, $options);
    }
    public function users_last_updated($from_date, $to_date, $flags = NULL, $options = NULL){
        return $this->last_updated('users', $from_date, $to_date, $flags, $options);
    }
    public function users($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('users', $search, $data, $flags, $options);
    }
    public function user_profiles_last_updated($from_date, $to_date, $flags = NULL, $options = NULL){
        return $this->last_updated('users.profiles', $from_date, $to_date, $flags, $options);
    }
    public function user_profiles($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('users.profiles', $search, $data, $flags, $options);
    }
    public function gettext_last_updated($from_date, $to_date, $flags = NULL, $options = NULL){
        return $this->last_updated('gettext', $from_date, $to_date, $flags, $options);
    }
    public function gettext($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('gettext', $search, $data, $flags, $options);
    }
    public function gapi($search = NULL, $flags = NULL, $options = NULL){
        return $this->send('gapi', $search, NULL, $flags, $options);
    }
    public function gettext_parsed($label, $value = NULL, $language = "en", $group_id = NULL, $advanced = NULL, $flags = NULL, $options = NULL){
        if($value) $data = ["value" => $value];
        else $data = NULL;

        if(is_null($group_id))
            $group_id = $this->auth['group'];

        $search = ["label" => $label, "language" => $language, "_group_id" => $group_id];

        if(!is_null($advanced)) $search["advanced"] = $advanced;

        return $this->send('gettext', $search, $data, $flags, $options);
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