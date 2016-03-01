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
    public $logger = null;

    function __construct($host, $port, $auth_key_id, $auth_token, $auth_group_id, $custom_client_id, $timeout = 15, $mode = NULL, $logger_params = null){

        if(!is_null($logger_params) && $logger_params){
            if(!isset($logger_params['file_path'])) $logger_params['file_path'] = null;
            if(!isset($logger_params['email'])) $logger_params['email'] = null;
            if(!isset($logger_params['console_out'])) $logger_params['console_out'] = false;
            if(!isset($logger_params['echo_out'])) $logger_params['echo_out'] = false;
            if(!isset($logger_params['remote_host'])) $logger_params['remote_host'] = null;

            $this->logger = new LOGGER(
                $this->logger_params["file_path"],
                $this->logger_params['remote_host'],
                $this->logger_params['email'],
                $this->logger_params['console_out'],
                $this->logger_params['echo_out']
            );
        } else {
            //default logger params
            $this->logger = new LOGGER('logs/sslib.log', null, null, false, true);
        }


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
        global $GROUP_ALT_ID;
        foreach($GROUP_ALT_ID as $k => $v){
            if($alt_id == $v) $result[]=$k;
        }
        if(count($result)) return $result;
        else return false;
    }

    private function group_id_2_domain_id($gid){
        global $GROUP_ALT_ID;

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
        if(isset($data['supplier_description'])) $result['description'] = $data['supplier_description'];
        if(isset($data['option_best_status'])) $result['status'] = $data['option_best_status'];
        if(isset($data['model_name'])) $result['model_name'] = $data['model_name'];
        if(isset($data['supplier_name'])) $result['supplier_name'] = $data['supplier_name'];
        if(isset($data['desigenr_name'])) $result['designer_name'] = $data['desigenr_name'];
        if(isset($data['brand_name'])) $result['brand_name'] = $data['brand_name'];
        if(isset($data['category_names'])) $result['categories'] = $data['category_names'];
        if(isset($data['tab'])) $result['main_category']=$data['tab'];
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

        if(isset($data['price_old'])) $result['price_old'] = $data['price_old'];
        else $result['price_old'] = $data['price'];

        if(isset($data["group_prices"])){
            foreach($data["group_prices"] as $k => $v) {
                $result["prices_domain"][$this->group_id_2_domain_id($k)] = $v;
            }
        }

        if(isset($data['__service'])) $result['__service'] = $data['__service'];

        foreach($data['specifications'] as $k => $v){ $result[$k] = $v; }
        foreach($data['options'] as $k => $v){ $result[$k] = $v; }
        foreach($data['stock'] as $k => $v){ $result[$k] = $v; }
        foreach($data['migration'] as $k => $v){ $result[$k] = $v; }

        if(isset($data['colours'])) $result['colours'] = $data['colours'];
        if(isset($data['description'])) $result['supplier_description'] = $data['description'];
        if(isset($data['status'])) $result['option_best_status'] = $data['status'];
        if(isset($data['model_name'])) $result['model_name'] = $data['model_name'];
        if(isset($data['supplier_name'])) $result['supplier_name'] = $data['supplier_name'];
        if(isset($data['designer_name'])) $result['designer_name'] = $data['designer_name'];
        if(isset($data['brand_name'])) $result['brand_name'] = $data['brand_name'];
        if(isset($data['category_name'])) $result['categories'] = $data['category_name'];
        if(isset($data['main_category'])) $result['tab']=$data['main_category'];
        if(isset($data['item_id'])) $result['item_id']=$data['item_id'];
        if(isset($data['option_id'])) $result['option_id']=$data['option_id'];

        return $result;
    }

    private function get_field_array($str, $separator)
    {
        $arr = explode($separator, rtrim($str, $separator));
        if (($arr[0] == '') && (count($arr) == 1)) {
            $arr = array();
        }

        return $arr;
    }

    private function omnis_json_encode($data)
    {
//	0	sku	==	ss_no	//		    string "124.99"
//	1	tab	==	type_name	//		    string "DESIGNER SUNGLASSES"
//	2	designer_name	==	brand	//		    string "Bolle"
//	3	supplier_name	==	supplier	//		    string "BOLL"
//	4	model_name	==	model	//		    string "Benton"
//	5	option_string	==	color_name	//		    string "2; 11566"
//	6	option_description	==	description	//		    string "Satin Black / Polarized A-14"
//	7	price	==	sell_price	//		    double "115.08"
//	8	rrp	==	rrp	//		    double "115.08"
//	9	supplier_description	==	hashs1	//		    string "(DESIGNER SUNGLASSES)"
//	10	thin_lens	==	thin_lens	//	!!!not used!!!	    bool
//	11	pd	==	webstock_rx_sunglasses_pd	//		    int "69"
//	12	category_names	==	ws_cat_list	//		    string "Metal;Polarised Lens;Graduated Lens;Square or Rectangular;Mens;Full rim;Prescription Compatible;Bifocal;Varifocal, Progressive, Multifocal;Degressive;"
//	13	discontinued	==	option_discontinued	//	!!!not used!!!	    string (!!!!!) Not get from Omnis! Set false below if hasn't "option_order" for DISCONTINUED option. see function convert.
//	14	featured,	==	featured	//		    bool
//	15	price_old	==	old_price	//		    double "115.08"
//	16	item_info	==	webstock_tempdesc	//		    ?
//	17	related_items	==	webstock_relateditems	//	!!!not used!!!	    ? (!!!!!) Not used
//	18	weight	==	webstock_shipband	//		    int
//	19	is_modified	==	webstock_modified	//		    bool
//	20	product_information	==	webstock_extendeddescription	//		    string
//	21	no_option_images	==	webstock_oldpicture	//		    bool
//	22	no_large_image	==	webstock_smallpicoption	//		    bool
//	23	frame_sizes	==	framesizes	//		    string "130_15_60_32:0:0:0;0_0_0_0:0:0:0;0_0_0_0:0:0:0"
//	24	warranty_period	==	webstock_warrantyperiod	//		    int
//	25	supp_name	==	webstock_parsesearch	//		    string "DG1253"
//	26	prices_domain	==	country_load	//		    string "1000/R10,5/X,"
//	27	unique_id	==	webstock_sequence	//	!!!not used!!!	    int
//	28	base_curve	==	webstock_basecurve	//		    string "6"
//	29	item_added	==	webstock_dateadded	//		    string "2012-05-21"
//	30	item_updated	==	webstock_dateupdated	//	!!!not used!!!	    string "2016-02-11"
//	31	is_brand	==	webstock_newbrand	//	!!!not used!!!	    bool
//	32	is_2_for_1	==	webstock_twoforone	//		    bool

        $result = [];
        $err = [];

        if(isset($data['sell_price'])) {
            $result['price'] = (float)$data['sell_price'];
            if (!is_numeric($result['price'])) { $err[] = 'sell_price is not numeric'; }
        }
        else $err[] = 'sell_price is required';

        if(isset($data['rrp'])) {
            $result['rr_price'] = (float)$data['rrp'];
            if (!is_numeric($result['rr_price'])) { $err[] = 'rrp is not numeric'; }
        }
        else $err[] = 'rrp is required';

        if(isset($data['old_price'])) {
            $result['old_price'] = (float)$data['old_price'];
            if (!is_numeric($result['old_price'])) { $err[] = 'old_price is not numeric'; }
        }

        if(isset($data['brand'])) {
            $result['designer_name'] = $data['brand'];
        }

        if(isset($data['supplier'])) {
            $result['supplier_name'] = $data['supplier'];
            if (strlen(trim($result['supplier_name']))<1) { $err[] = 'supplier cant be empty'; }
        }
        else $err[] = 'supplier is required';

        if(isset($data['model'])) {
            $result['model_name'] = $data['model'];
            if (strlen(trim($result['model_name']))<1) { $err[] = 'model cant be empty'; }
        }
        else $err[] = 'model is required';

        if(isset($data['description'])) $result['options']['option_description'] = $data['description'];
        else $err[] = 'description is required';

        if(isset($data['hashs1'])) $result['description'] = $data['hashs1'];
        else $err[] = 'hashs1 is required';

        if(isset($data['webstock_rx_sunglasses_pd'])) $result['specifications']['pd'] = $data['webstock_rx_sunglasses_pd'];
        else $err[] = 'webstock_rx_sunglasses_pd is required';

        if(isset($data['featured'])) $result['stock']['featured'] = $data['featured'];
        else $err[] = 'featured is required';

        if(isset($data['webstock_tempdesc'])) $result['migration']['item_info'] = $data['webstock_tempdesc'];
        else $err[] = 'webstock_tempdesc is required';

        if(isset($data['webstock_shipband'])) {
            $result['specifications']['weight'] = $data['webstock_shipband'];
            if (!is_numeric($result['specifications']['weight'])) { $err[] = 'webstock_shipband is not numeric'; }
        }
        else $err[] = 'webstock_shipband is required';

        if(isset($data['webstock_modified'])) $result['migration']['is_modified'] = $data['webstock_modified'];
        else $err[] = 'webstock_modified is required';

        if(isset($data['webstock_extendeddescription'])) $result['migration']['product_information'] = $data['webstock_extendeddescription'];
        else $err[] = 'webstock_extendeddescription is required';

        if(isset($data['webstock_oldpicture'])) $result['migration']['no_option_images'] = $data['webstock_oldpicture'];
        else $err[] = 'webstock_oldpicture is required';

        if(isset($data['webstock_smallpicoption'])) $result['migration']['no_large_image'] = $data['webstock_smallpicoption'];
        else $err[] = 'webstock_smallpicoption is required';

        if(isset($data['webstock_warrantyperiod'])) $result['specifications']['warranty_period'] = $data['webstock_warrantyperiod'];
        else $err[] = 'webstock_warrantyperiod is required';

        if(isset($data['webstock_parsesearch'])) $result['migration']['supp_name'] = $data['webstock_parsesearch'];
        else $err[] = 'webstock_parsesearch is required';

        if(isset($data['color_name'])) {
            if (strpos($data['color_name'], ';') === false) {
                $result['stock']['discontinued'] = true;
                $result['options']['option_order'] = 0;
                $result['options']['option_name'] = $data['color_name'];
            } else {
                $result['stock']['discontinued'] = false;

                @list($result['options']['option_order'], $result['options']['option_name']) = $this->get_field_array($data['color_name'], ';');
                $result['options']['option_name'] = trim($result['options']['option_name']);
            }

            if(!isset($result['options']['option_name'])) $err[] = 'option_name is required';
            if (!is_numeric($result['options']['option_order'])) { $err[] = 'option_order is not numeric'; }
        }
        else $err[] = 'color_name is required';

        if(isset($data['ss_no'])){
            $result['item_id'] = intval(str_replace('.', '', $data['ss_no']));

            if (!is_numeric($result['item_id'])) { $err[] = 'ss_no is not numeric'; }

            if(isset($result['options']['option_order'])) {
                $result['option_id'] = intval($result['item_id'].sprintf('%02d', $result['options']['option_order']));
            }
            else $err[] = 'option_order is required';
        }
        else $err[] = 'ss_no is required';

        if(isset($data['ws_cat_list'])) $result['categories'] = $this->get_field_array($data['ws_cat_list'], ';');
        else $err[] = 'ws_cat_list is required';

        // Prepare tab
        if(isset($data['type_name'])) {
            $result['main_category'] = $this->get_field_array($data['type_name'], ';');

            if (isset($result['main_category'][0]) && (($result['main_category'][0] == 'SERVICES') || ($result['main_category'][0] == 'NULL'))) {
                $result['main_category'] = array();
                $result['categories'] = array('Other');
            }

            if (in_array('Prescription Compatible', $result['categories'])) {
                $result['main_category'] = array('DESIGNER SUNGLASSES', 'PRESCRIPTION SUNGLASSES');
            }

            switch (count($result['main_category'])) {
                case 0:
                    $result['main_category'] = 'Accessories';
                    break;
                case 1:
                    $result['main_category'] = $result['main_category'][0];
                    break;
                default:
                    $result['main_category'] = 'Prescription Sunglasses';
            }
        }
        else $err[] = 'type_name is required';

        // Prepare frame sizes and status option
        $result['status'] = 'DISCONTINUED';

        if(isset($data['framesizes'])) {
            $arr = array();
            $result['specifications']['frame_sizes'] = $this->get_field_array($data['framesizes'], ';');
            foreach ($result['specifications']['frame_sizes'] as $frame_size) {
                @list($sizes, $disc, $back, $stock) = $this->get_field_array($frame_size, ':');
                @list($arm, $bridge, $lens, $height) = $this->get_field_array($sizes, '_');
                if (($arm == 0) && ($bridge == 0) && ($lens == 0) && ($height == 0)) {
                    continue;
                }
                $status = 'IN_STOCK';
                if ($disc) {
                    $status = 'DISCONTINUED';
                } else if ($back) {
                    $status = 'IN_STOCK';
                }
                if ($result['status'] === 'DISCONTINUED' && $status != 'DISCONTINUED') {
                    $result['status'] = $status;
                }
                if ($result['status'] === 'BACK_ORDERED' && $status === 'IN_STOCK') {
                    $result['status'] = 'IN_STOCK';
                }
                $arr[] = array('arm' => $arm, 'bridge' => $bridge, 'lens' => $lens, 'height' => $height, 'disk' => $disc, 'back' => $back, 'stock' => $stock, 'status' => $status);
            }
            $result['specifications']['frame_sizes'] = $arr;
        }
        else $err[] = 'framesizes is required';

        if(isset($data['country_load'])) {
            $arr = array();
            $data['country_load'] = $this->get_field_array($data['country_load'], ',');
            foreach ($data['country_load'] as $domain_price) {
                // We can get from omnis: "1000/R10" or "1000/-20" or "1000/-20/R10" or "1000/R10/-20" or 1000/X
                $dp = $this->get_field_array($domain_price, '/');
                $domain_id = $dp[0];

                if (strpos($domain_price, "X") !== false) {
                    $arr[$domain_id]['price'] = 0;
                    $arr[$domain_id]['price_old'] = $result['price'];
                } else {
                    $percent = NULL;
                    $round = NULL;

                    if (isset($dp[1])) {
                        if ($dp[1][0] == 'R') {
                            $round = substr($dp[1], 1);
                        } else {
                            $percent = $dp[1];
                        }
                    }

                    if (isset($dp[2])) {
                        if ($dp[2][0] == 'R') {
                            $round = substr($dp[2], 1);
                        } else {
                            $percent = $dp[2];
                        }
                    }

                    $arr[$domain_id]['price_old'] = $result['price'];
                    $arr[$domain_id]['price'] = $result['price'];
                    if (!is_null($percent) && $percent) {
                        $arr[$domain_id]['percent'] = $percent;
                        $arr[$domain_id]['price'] = round($arr[$domain_id]['price'] * (1 + $percent / 100), 2);
                    }
                    if (!is_null($round)) {
                        $arr[$domain_id]['round'] = $round;
                        $arr[$domain_id]['price'] = (ceil($arr[$domain_id]['price'] / $round)) * $round;
                    }
                }
            }
            if (count($arr)) {
                foreach($arr as $k => $v) {
                    $gp = $this->domain_id_2_group_id($k);
                    foreach($gp as $gid){ $result['group_prices'][$gid] = $v; }
                }
//                $result['group_prices'] = $arr;
            }
        }

        // recognize new items (added <= 3 months ago), add them category 'new'
        if(isset($data['webstock_dateadded'])) {

            if(isset($data['webstock_dateadded'])) $result['migration']['item_added'] = $data['webstock_dateadded'];

            $added = strtotime($data['webstock_dateadded']);

            if ($added >= strtotime('-3 month')) {
                $result['categories'][] = 'New Items';
            }

            if($result['migration']['item_added'] == ''){
                $err[] = 'webstock_dateadded is cant be empty';
            } elseif($result['migration']['item_added'] == 'NULL'){
                $err[] = 'webstock_dateadded is cant be NULL';
            } elseif (!$added || ($added > time())) {
                $err[] = 'webstock_dateadded is cant be in future';
            }
        }
        else $err[] = 'webstock_dateadded is required';

        // promo 2 for 1 Added into category
        if(isset($data['webstock_twoforone'])) {
            if ($data['webstock_twoforone'] === 'YES' || $data['webstock_twoforone'] === true) {
                $result['categories'][] = '2 for 1';
                $result['two_for_one'] = true;
            }
        }

        // if is prescription sunglasses, add category 'RX'
        if ($result['main_category'] == 'Prescription Sunglasses') {
            $rusult['categories'][] = 'Prescription Compatible';
        }

        if(isset($data['webstock_basecurve'])){
            if ($data['webstock_basecurve'] == 0) {
                $result['specifications']['base_curve'] = 2;
            } else {
                $result['specifications']['base_curve'] = $data['webstock_basecurve'];
            }
        }
        else $err[] = 'webstock_basecurve is required';

        if(count($err)){
            return [ "result"=> false, "errors"=> $err, "ss_no" => (isset($data['ss_no']) ? $data['ss_no'] : 'Unknown_number'), "option_id" => (isset($result['option_id']) ? $result['option_id'] : 'Unknown_option_id') ];

        } else {
            return [ "result"=> true, "data"=>$result];
        }
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
//        $options['protocol_version'] = 2;
//        $options['client_id'] = $this->client_id;
        $options['protocol_version'] = $this->protocol_version;
        $message['options'] = $options;

        return $message;
    }

    private function method_parser($method, $search = NULL, $data = NULL, $flags = NULL, $options = NULL) {
        $method_type = NULL;
        if(!is_null($flags) && ($flags & SSAPI_AGGREGATOR)) {
            $method_type = '.aggregator';
        } else {
            if (!is_null($search) && !is_null($data)) {
                $method_type = '.update';
            } elseif (!is_null($data)) {
                $method_type = '.add';
            } elseif (!is_null($search)) {
                if (!is_null($flags) && $flags & SSAPI_DELETE_DOCUMENT) {
                    $method_type = '.delete';
                } else {
                    $method_type = '.get';
                }
            }
        }

        if($method_type) {
            return ($method . $method_type);
        } else {
            return FALSE;
        }
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
        $method = $this->method_parser($method, $search, $data, $flags, $options);
        if($method) {
            return $this->message_sender(
                $method,
                $this->message_parser($search, $data, $flags, $options)
            );
        } else {
            return false;
        }
    }

    private function last_updated($type, $from_date, $to_date, $flags = NULL, $options = NULL){
        $search = array();
        $search['__service.client_id']['$ne'] = $this->client_id;

        if(!isset($options['order'])){
            $options['order'] = ["__service.updated" => 1];
        }

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

    private function last_updated_minmax($type, $from_date, $to_date){
        $search['__service.client_id']['$ne'] = $this->client_id;

        if(!is_null($to_date)) {
            $search['__service.updated']['$lte'] =  $to_date;
        }
        if(!is_null($from_date)) {
            $search['__service.updated']['$gt'] = $from_date;
        }

        $search = [
                ['$match' => $search ],
                ['$group' => [
                    '_id' => null,
                    'max' => [ '$max' => '$__service.updated' ],
                    'min' => [ '$min' => '$__service.updated' ]
                    ]
                ]
            ];


        /*
                 $search = ['q' => [
                    ['__service.client_id' => ['$ne' => $this->client_id] ],
                    ['__service.updated' => [['$gt' => $from_date], ['$lte' => $to_date] ]]
                ]];
        */
        return $this->send($type, $search, NULL, SSAPI_AGGREGATOR, NULL);
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
        $result = $this->last_updated('items', $from_date, $to_date, $flags, $options);
        if($result && !is_null($flags) && ($flags & SSAPI_CONVERTER_WEB)) {
            foreach($result as $k => $v){
                $result[$k] = $this->web_json_decode($v);
            }
        }
        return $result;
    }
    public function items_last_updated_minmax($from_date, $to_date){
        $result = $this->last_updated_minmax('items', $from_date, $to_date);
        return $result;
    }

    public function items($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        if($data && !is_null($flags))
        {
            if($flags & SSAPI_CONVERTER_WEB) {
                if ($flags & SSAPI_MULTI_QUERY) {
                    foreach ($data as $k => $v) {
                        $data[$k] = $this->web_json_encode($v);
                    }
                } else {
                    $data = $this->web_json_encode($data);
                }
            } elseif($flags & SSAPI_CONVERTER_OMNIS) {
                if($flags & SSAPI_MULTI_QUERY) {
                    foreach ($data as $k => $v) {
                        $data[$k] = $this->omnis_json_encode($v);
                        if($data[$k]['result']){
                            $data[$k] = $data[$k]['data'];
                        } else {
//                            print_r($data[$k]['errors']);
                            $this->logger->error(["ss_no" => $data[$k]['ss_no'], "option_id" => $data[$k]['option_id'], "errors" => $data[$k]['errors']]);
                            unset($data[$k]);
                        }
                    }
                } else {
                    $data = $this->omnis_json_encode($data);
                    if($data['result']){
                        $data=$data['data'];
                    } else {
//                        print_r($data['errors']);
                        $this->logger->error(["ss_no" => $data['ss_no'], "option_id" => $data['option_id'], "errors" => $data['errors']]);
                        return false;
                    }
                }
            }
        }

        $result = $this->send('items', $search, $data, $flags, $options);
        if($result && !is_null($flags)) {
            if($flags & SSAPI_CONVERTER_WEB) {
                foreach ($result as $k => $v) {
                    $result[$k] = $this->web_json_decode($v);
                }
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