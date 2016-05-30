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
    private $timeout = 60000;
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

        $this->timeout = $timeout*1000; // convert to ms


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
            'client_id' => $this->client_id,
            'socketTimeout' => $this->timeout
        ]);

        if(!$request->isError()){

            $request2 = $this->connection->sendRequest('auth.vrf', [
                'token' => $this->auth['token'],
                'socketTimeout' => $this->timeout
            ]);

            if(!$request2->isError()){
                if($request2->result){
//                    if($this->mode & SSAPI_CONNECTION_NOTIFYS_ENABLE){
//                        $this->notif_connection->sendNotification('auth.nrm', [
//                            '_key_id' => $this->auth['_key_id'],
//                            '_group_id' => $this->auth['_group_id'],
//                            'token' => $this->auth['token'],
//                            'client_id' => $this->client_id,
//                            'socketTimeout' => $this->timeout
//                        ]);
//                    }

                    return true;
                }
            } else { throw new Exception\ConnectionException('auth: cant get answer 2'); }

        } else { throw new Exception\ConnectionException('auth: cant get answer 1'); }

        return false;
    }

    public function domain_id_2_group_id($alt_id){
        $alt_id = (int)$alt_id;
        $result =[];

        include(dirname(__FILE__).'/../config_groups.php');

        foreach($GROUP_ALT_ID as $k => $v){
            if($alt_id == $v) $result[]=$k;
        }
        if(count($result)) return $result;
        else return false;
    }

    private function group_id_2_domain_id($gid){
        include dirname(__FILE__) . '/../config_groups.php';

        if(isset($GROUP_ALT_ID[$gid])) return $GROUP_ALT_ID[$gid];
        else return false;
    }

    public function web_json_decode_fromitems(&$items){
        $result = [];
        $items_i=0;
        $options_i=0;
        foreach($items as $key => $item) {
            $items_i++;
            unset($items[$key]);
            $data = [];

            if(isset($item['specifications'])) $data = array_merge($data, $item['specifications']);
            if(isset($item['stock'])) $data = array_merge($data, $item['stock']);
            if(isset($item['migration'])) $data = array_merge($data, $item['migration']);

            if(!isset($item['dateadded']) || !$item['dateadded']) $data['item_added'] = date("Y-m-d H:i:s");

            if(isset($item['_id'])) $data['_api_item_id'] = $item['_id'];
            if(isset($item['model_name'])) $data['model_name'] = $item['model_name'];
            if(isset($item['supplier_name'])) $data['supplier_name'] = $item['supplier_name'];
            if(isset($item['designer_name'])) $data['designer_name'] = $item['designer_name'];
            if(isset($item['brand_name'])) $data['brand_name'] = $item['brand_name'];
            if(isset($item['categories'])) $data['category_names'] = $item['categories'];
            if(isset($item['main_category'])) $data['tab']=$item['main_category'];
            if(isset($item['item_number'])) $data['item_id']=$item['item_number'];
            if(isset($item['description'])) $data['supplier_description'] = $item['description'];
            if(isset($item['__service'])) $data['__service'] = $item['__service'];
            
            //$item['options'];
//==========---------------------------------------------------------------------------------

            foreach($item['options'] as $option){
                $options_i++;
                $option_data = $data;

                if(isset($option['specifications'])) $option_data = array_merge($option_data, $option['specifications']);
                if(isset($option['migration'])) $option_data = array_merge($option_data, $option['migration']);
                if(isset($option['stock'])) $option_data = array_merge($option_data, $option['stock']);

                if(isset($option['_id'])) $data['_api_option_id'] = $option['_id'];
                if(isset($option['option_number'])) $option_data['option_id']=$option['option_number'];
                if(isset($option['status'])) $option_data['option_best_status'] = $option['status'];
                if(isset($option['order'])) $option_data['option_order']=$option['order'];
                if(isset($option['name'])) $option_data['option_name']=$option['name'];
                if(isset($option['description'])) $option_data['option_description']=$option['description'];

                if(isset($option["price"])) $option_data["price"] = (double)$option["price"];

                if(isset($option["price_retailer"])) $option_data["rrp"] = (double)$option["price_retailer"];
                else $option_data['rrp'] = $option['price'];

                if(isset($option['price_old'])) $option_data['price_old'] = (double)$option['price_old'];
                else $option_data['price_old'] = $option['price'];

                if(!isset($option_data['item_info'])) $option_data['item_info'] = '';
                if(!isset($option_data['supp_name'])) $option_data['supp_name'] = '';
                if(!isset($option_data['no_large_image'])) $option_data['no_large_image'] = 0;
                if(!isset($option_data['no_option_images'])) $option_data['no_option_images'] = 0;
                if(!isset($option_data['product_information'])) $option_data['product_information'] = '';
                
                include dirname(__FILE__) . '/../config_groups.php';
                foreach($GROUP_ALT_ID as $GAID){
                    if (isset($option['price'])) $option_data["prices_domain"][$GAID]['price'] = $option['price'];
                    elseif (isset($data['price'])) $option_data["prices_domain"][$GAID]['price'] = $data['price'];

                    if(isset($option['price_old'])) $option_data["prices_domain"][$GAID]['price_old'] = $option['price_old'];
                    elseif (isset($data['price_old'])) $option_data["prices_domain"][$GAID]['price_old'] = $data['price_old'];
                    elseif (isset($option['price'])) $option_data["prices_domain"][$GAID]['price_old'] = $option['price'];
                    elseif (isset($data['price'])) $option_data["prices_domain"][$GAID]['price_old'] = $data['price'];
                }

                if(isset($option["group_prices"])){
                    foreach($option["group_prices"] as $k => $v) {
                        if(isset($v['price']) && (!$v['price'] || $v['price'] == 0 || $v['price'] == '0' || $v['price']=='x' || $v['price']=='X')){
                            unset($option_data["prices_domain"][$this->group_id_2_domain_id($v['_group_id'])]);
                        } else {
                            $domain_price = [];
                            if (isset($v['price'])) $domain_price['price'] = $v['price'];
                            elseif (isset($option['price'])) $domain_price['price'] = $option['price'];
                            elseif (isset($data['price'])) $domain_price['price'] = $data['price'];

                            if (isset($v['price_old'])) $domain_price['price_old'] = $v['price_old'];
                            elseif (isset($option['price_old'])) $domain_price['price_old'] = $option['price_old'];
                            elseif (isset($data['price_old'])) $domain_price['price_old'] = $data['price_old'];
                            elseif (isset($option['price'])) $domain_price['price_old'] = $option['price'];
                            elseif (isset($data['price'])) $domain_price['price_old'] = $data['price'];

                            $option_data["prices_domain"][$this->group_id_2_domain_id($v['_group_id'])] = $domain_price;
                        }
                    }
                }

                $result[]=$option_data;
            }
        }

//        print_r($result);
//        echo "\n*****\nfrom $items_i items we created $options_i options\n\n";
//        die;

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

    public function omnis_json_encode_byitems($data)
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
            $result['options']['price'] = (float)$data['sell_price'];
            if (!is_numeric($result['options']['price'])) { $err[] = 'sell_price is not numeric'; }
        }
        else $err[] = 'sell_price is required';

        if(isset($data['rrp'])) {
            $result['options']['price_retailer'] = (float)$data['rrp'];
            if (!is_numeric($result['options']['price_retailer'])) { $err[] = 'rrp is not numeric'; }
        }
        else $err[] = 'rrp is required';

        if(isset($data['old_price'])) {
            $result['options']['price_old'] = (float)$data['old_price'];
            if (!is_numeric($result['options']['price_old'])) { $err[] = 'old_price is not numeric'; }
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

        if(isset($data['description'])) $result['options']['description'] = $data['description'];
        else $result['options']['description'] = "";

        if(isset($data['hashs1'])) $result['description'] = $data['hashs1'];
        else $result['description'] = "";

        if(isset($data['webstock_rx_sunglasses_pd'])) $result['specifications']['pd'] = $data['webstock_rx_sunglasses_pd'];
        else $err[] = 'webstock_rx_sunglasses_pd is required';

        if(isset($data['featured'])) $result['options']['stock']['featured'] = $data['featured'];
        else $err[] = 'featured is required';

        if(isset($data['webstock_tempdesc']) or is_null($data['webstock_tempdesc'])) $result['migration']['item_info'] = $data['webstock_tempdesc'];
        else $err[] = 'webstock_tempdesc is required';

        if(isset($data['webstock_shipband'])) {
            $result['specifications']['weight'] = $data['webstock_shipband'];
            if (!is_numeric($result['specifications']['weight'])) { $err[] = 'webstock_shipband is not numeric'; }
        }
        else $err[] = 'webstock_shipband is required';

        if(isset($data['webstock_modified'])) $result['migration']['is_modified'] = $data['webstock_modified'];
        else $err[] = 'webstock_modified is required';

        if(isset($data['webstock_extendeddescription']) or is_null($data['webstock_extendeddescription'])) $result['migration']['product_information'] = $data['webstock_extendeddescription'];
        else $err[] = 'webstock_extendeddescription is required';

        if(isset($data['webstock_oldpicture']) or is_null($data['webstock_oldpicture']) ) $result['migration']['no_option_images'] = $data['webstock_oldpicture'];
        else $err[] = 'webstock_oldpicture is required';

        if(isset($data['webstock_smallpicoption']) or is_null($data['webstock_smallpicoption'])) $result['options']['migration']['no_large_image'] = $data['webstock_smallpicoption'];
        else $err[] = 'webstock_smallpicoption is required';

        if(isset($data['webstock_warrantyperiod'])) $result['specifications']['warranty_period'] = $data['webstock_warrantyperiod'];
        else $err[] = 'webstock_warrantyperiod is required';

        if(isset($data['webstock_parsesearch']) or is_null($data['webstock_parsesearch'])) $result['migration']['supp_name'] = $data['webstock_parsesearch'];
        else $err[] = 'webstock_parsesearch is required';

        if(isset($data['color_name'])) {
            if (strpos($data['color_name'], ';') === false) {
                $result['options']['stock']['discontinued'] = true;
                $result['options']['order'] = 0;
                $result['options']['name'] = $data['color_name'];
            } else {
                $result['options']['stock']['discontinued'] = false;

                @list($result['options']['order'], $result['options']['name']) = $this->get_field_array($data['color_name'], ';');
                $result['options']['order'] = trim($result['options']['order']);
                $result['options']['name'] = trim($result['options']['name']);
            }

            if(!isset($result['options']['name'])) $err[] = 'option name is required';
            if (!is_numeric($result['options']['order'])) { $err[] = 'option order is not numeric'; }
        }
        else $err[] = 'color_name is required';

        if(isset($data['ss_no'])){
            $result['item_number'] = intval(str_replace('.', '', $data['ss_no']));

            if (!is_numeric($result['item_number'])) { $err[] = 'ss_no is not numeric'; }

            if(isset($result['options']['order'])) {
                $result['options']['option_number'] = intval($result['item_number'].sprintf('%02d', $result['options']['order']));
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
        $result['options']['status'] = 'DISCONTINUED';

        if(isset($data['framesizes'])) {
            $arr = array();
            $result['options']['specifications']['frame_sizes'] = $this->get_field_array($data['framesizes'], ';');
            foreach ($result['options']['specifications']['frame_sizes'] as $frame_size) {
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
                if ($result['options']['status'] == 'DISCONTINUED' && $status != 'DISCONTINUED') {
                    $result['options']['status'] = $status;
                }
                if ($result['options']['status'] == 'BACK_ORDERED' && $status == 'IN_STOCK') {
                    $result['options']['status'] = 'IN_STOCK';
                }
                $arr[] = array('arm' => $arm, 'bridge' => $bridge, 'lens' => $lens, 'height' => $height, 'stock' => $stock, 'status' => $status);
            }
            $result['options']['specifications']['frame_sizes'] = $arr;
        }
        else $err[] = 'framesizes is required';

        if(isset($data['country_load'])) {
            $arr = array();
            $data['country_load'] = $this->get_field_array($data['country_load'], ',');
            foreach ($data['country_load'] as $domain_price) {
                if(!isset($result['options']['price'])) {
                    $err[] = 'sell_price is not defined and domain price can be set';
                    break;
                }
                // We can get from omnis: "1000/R10" or "1000/-20" or "1000/-20/R10" or "1000/R10/-20" or 1000/X
                $dp = $this->get_field_array($domain_price, '/');
                $domain_id = $dp[0];

                if (strpos($domain_price, "X") !== false) {
                    $arr[$domain_id]['price'] = 0;
                    $arr[$domain_id]['price_old'] = $result['options']['price'];
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

                    $arr[$domain_id]['price_old'] = $result['options']['price'];
                    $arr[$domain_id]['price'] = $result['options']['price'];

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
                    $groups = $this->domain_id_2_group_id($k);
                    $used_groups = [];
                    foreach($groups as $g){
                        if(isset($used_groups[$g])) continue;
                        else $used_groups[$g]=1;

                        $v['_group_id'] = $g;
                        $result['options']['group_prices'][] = $v;
                    }
                }
            }
        }

        // recognize new items (added <= 3 months ago), add them category 'new'
        if(isset($data['webstock_dateadded'])) {

            if(is_null($data['webstock_dateadded'])) {
                $result['categories'][] = 'New Items';
            } else {
                $result['migration']['item_added'] = $data['webstock_dateadded'];

                $added = strtotime($data['webstock_dateadded']);

                if ($added >= strtotime('-3 month')) {
                    $result['categories'][] = 'New Items';
                }

                if ($result['migration']['item_added'] == '') {
                    $err[] = 'webstock_dateadded is cant be empty';
                } elseif ($result['migration']['item_added'] == 'NULL') {
                    $err[] = 'webstock_dateadded is cant be NULL';
                } elseif (!$added || ($added > (time() + 43200))) { //+12 hours for ignore timezone differences
                    $err[] = 'webstock_dateadded is cant be in future';
                }
            }
        } else {
            $result['categories'][] = 'New Items';
        }

        // promo 2 for 1 Added into category
        if(isset($data['webstock_twoforone'])) {
            if ($data['webstock_twoforone'] === 'YES' || $data['webstock_twoforone'] === true) {
                $result['categories'][] = '2 for 1';
                $result['options']['stock']['two_for_one'] = true;
            }
        }

        // if is prescription sunglasses, add category 'RX'
        if (isset($result['main_category']) && $result['main_category'] == 'Prescription Sunglasses') {
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
            return [ "result"=> false, "errors"=> $err, "ss_no" => (isset($data['ss_no']) ? $data['ss_no'] : 'Unknown_number'), "option_number" => (isset($result['option_number']) ? $result['option_number'] : 'Unknown_option_number') ];

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

    public function send($method, $search = NULL, $data = NULL, $flags = NULL, $options = NULL) {
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

    public function last_updated($type, $from_date, $to_date, $flags = NULL, $options = NULL){
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

    public function web_json_encode_orders(
        &$data, //pointer to source data for fastest parsing and safest for memory  
        $isMulti //flag what mean is array of items or just one value
    ){
        
        if($isMulti){ //if is array - parsing via recursive method of itself calling
            foreach($data as $k => $v){
                if(!$this->web_json_encode_orders($data[$k], FALSE)){
                    return false;
                }
            }
            return $data;
        }

        if(isset($data['id'])) $data['order_number'] = $data['id'];
        if(isset($data['user_id'])) $data['user_number'] = $data['user_id'];
        if(isset($data['profile_id'])) $data['profile_number'] = $data['profile_id'];

        unset($data['id']);
        unset($data['user_id']);
        unset($data['profile_id']);

        if(isset($data['domain_id'])) { $data['migration']['domain_id'] = $data['domain_id']; unset($data['domain_id']); }
        if(isset($data['store_id'])) { $data['migration']['store_id'] = $data['store_id']; unset($data['store_id']); }
        if(isset($data['staff_id'])) { $data['migration']['staff_id'] = $data['staff_id']; unset($data['staff_id']); }
        if(isset($data['ipn_user_data'])) { $data['migration']['ipn_user_data'] = $data['ipn_user_data']; unset($data['ipn_user_data']); }
        if(isset($data['ipn_email_sent'])) { $data['migration']['ipn_email_sent'] = $data['ipn_email_sent']; unset($data['ipn_email_sent']); }
        if(isset($data['checkout_page_email_sent'])) { $data['migration']['checkout_page_email_sent'] = $data['checkout_page_email_sent']; unset($data['checkout_page_email_sent']); }
        if(isset($data['checkout_page_visited'])) { $data['migration']['checkout_page_visited'] = $data['checkout_page_visited']; unset($data['checkout_page_visited']); }
        if(isset($data['reference'])) { $data['migration']['reference'] = $data['reference']; unset($data['reference']); }
        if(isset($data['errors'])) { $data['migration']['errors'] = $data['errors']; unset($data['errors']); }
        if(isset($data['parent_id'])) { $data['migration']['parent_id'] = $data['parent_id']; unset($data['parent_id']); }
        if(isset($data['exported'])) { $data['migration']['exported'] = $data['exported']; unset($data['exported']); }
        if(isset($data['currency_id'])) { $data['migration']['currency_id'] = $data['currency_id']; unset($data['currency_id']); }
        if(isset($data['billing_address_id'])) { $data['migration']['billing_address_id'] = $data['billing_address_id']; unset($data['billing_address_id']); }
        if(isset($data['reference'])) { $data['migration']['reference'] = $data['reference']; unset($data['reference']); }
        if(isset($data['order_ip'])) { $data['migration']['order_ip'] = $data['order_ip']; unset($data['order_ip']); }
        if(isset($data['delivery_method_id'])) { $data['migration']['delivery_method_id'] = $data['delivery_method_id']; unset($data['delivery_method_id']); }
        if(isset($data['delivery_address_id'])) { $data['migration']['delivery_address_id'] = $data['delivery_address_id']; unset($data['delivery_address_id']); }
        if(isset($data['addresschanged'])) { $data['migration']['addresschanged'] = $data['addresschanged']; unset($data['addresschanged']); }
        if(isset($data['billing_address_id'])) { $data['migration']['billing_address_id'] = $data['billing_address_id']; unset($data['billing_address_id']); }

        if(isset($data['checkout_at'])) { $data['dates']['checkout_at'] = $data['checkout_at']; unset($data['checkout_at']); }
        if(isset($data['created_at'])) { $data['dates']['created_at'] = $data['created_at']; unset($data['created_at']); }
        if(isset($data['updated_at'])) { $data['dates']['updated_at'] = $data['updated_at']; unset($data['updated_at']); }

        if(isset($data['currency_id'])) { $data['bill']['currency_id'] = $data['currency_id']; unset($data['currency_id']); }
        if(isset($data['final_total'])) { $data['bill']['final_total'] = $data['final_total']; unset($data['final_total']); }
        if(isset($data['price_extra'])) { $data['bill']['price_extra'] = $data['price_extra']; unset($data['price_extra']); }
        if(isset($data['total_save_two_for_one'])) { $data['bill']['total_save_two_for_one'] = $data['total_save_two_for_one']; unset($data['total_save_two_for_one']); }
        if(isset($data['order_amount'])) { $data['bill']['order_amount'] = $data['order_amount']; unset($data['order_amount']); }
        if(isset($data['promo_discount'])) { $data['bill']['promo_discount'] = $data['promo_discount']; unset($data['promo_discount']); }
        if(isset($data['promo_code_id'])) { $data['bill']['promo_code_id'] = $data['promo_code_id']; unset($data['promo_code_id']); }
        if(isset($data['pcode_value'])) { $data['bill']['pcode_value'] = $data['pcode_value']; unset($data['pcode_value']); }
        if(isset($data['beauty_card'])) { $data['bill']['beauty_card'] = $data['beauty_card']; unset($data['beauty_card']); }
        if(isset($data['shipping_cost'])) { $data['bill']['shipping_cost'] = $data['shipping_cost']; unset($data['shipping_cost']); }
        if(isset($data['vat_perc'])) { $data['bill']['vat_perc'] = $data['vat_perc']; unset($data['vat_perc']); }
        if(isset($data['rate'])) { $data['bill']['rate'] = $data['rate']; unset($data['rate']); }
        if(isset($data['payment_record_id'])) { $data['bill']['payment_record_id'] = $data['payment_record_id']; unset($data['payment_record_id']); }
        if(isset($data['payment_system_id'])) { $data['bill']['payment_system_id'] = $data['payment_system_id']; unset($data['payment_system_id']); }
        if(isset($data['parent_id'])) { $data['bill']['parent_id'] = $data['parent_id']; unset($data['parent_id']); }
        if(isset($data['code'])) { $data['bill']['code'] = $data['code']; unset($data['code']); }
        if(isset($data['number'])) { $data['bill']['number'] = $data['number']; unset($data['number']); }

        if(isset($data['items']) && $data['items']) {
            foreach($data['items'] as $k => $v){

                if(isset($v["item_id"])) { $data['items'][$k]["item_number"] = $v["item_id"]; unset($data['items'][$k]["item_id"]); }
                if(isset($v["item_id"])) { $data['items'][$k]["ordered_item_number"] = $v["id"]; unset($data['items'][$k]["id"]); }
                if(isset($v["item_id"])) { $data['items'][$k]["ordered_item_type"] = $v["type"]; unset($data['items'][$k]["type"]); }

                if(isset($data['items'][$k]['prescription_id'])) unset($data['items'][$k]['prescription_id']);

                if(isset($v['prescription']) && $v['prescription'] && isset($v['prescription']['id'])){
                    $data['items'][$k]['prescription']['prescription_number'] = $v['prescription']['id'];
                    unset($data['items'][$k]['prescription']['id']);
                }

            }
        }

        return $data;
    }

    public function web_json_decode_orders(
        &$data, //pointer to source data for fastest parsing and safest for memory
        $isMulti //flag what mean is array of items or just one value
    ){

        if($isMulti){ //if is array - parsing via recursive method of itself calling
            foreach($data as $k => $v){
                if(!$this->web_json_decode_orders($data[$k], FALSE)){
                    return false;
                }
            }
            return $data;
        }

        if(isset($data['order_number'])) $data['id'] = $data['order_number'];
        if(isset($data['user_number'])) $data['user_id'] = $data['user_number'];
        if(isset($data['profile_number'])) $data['profile_id'] = $data['profile_number'];

        if(isset($data['migration'])) { $data = array_merge($data, $data['migration']); unset($data['migration']); }
        if(isset($data['dates'])) { $data = array_merge($data, $data['dates']); unset($data['dates']); }
        if(isset($data['bill'])) { $data = array_merge($data, $data['bill']); unset($data['bill']); }

        if(isset($data['items']) && $data['items']) {
            foreach($data['items'] as $k => $v) {

                if(isset($v["item_number"])) $data['items'][$k]["item_id"] = $v["item_number"];
                if(isset($v["ordered_item_number"])) $data['items'][$k]["id"] = $v["ordered_item_number"];
                if(isset($v["ordered_item_type"])) $data['items'][$k]["type"] = $v["ordered_item_type"];

                if(isset($v["order_number"])) $data['items'][$k]['order_id'] = $data['order_number'];
                if(isset($v["user_number"])) $data['items'][$k]['user_id'] = $data['user_number'];
                if(isset($v["profile_number"])) $data['items'][$k]['profile_id'] = $data['profile_number'];


                if(isset($v['prescription']) && $v['prescription'] && isset($v['prescription']['id'])){

                    if(isset($v['prescription']['prescription_number'])) {
                        $data['items'][$k]['prescription_id'] = $v['prescription']['prescription_number'];
                        $data['items'][$k]['prescription']['id'] = $v['prescription']['prescription_number'];
                    }

                    if(isset($data['profile_number'])) $data['items'][$k]['prescription']['profile_id'] = $data['profile_number'];
                }

            }
        }

        return $data;
    }


//===========================================================================================
//=======================   P U B L I C   F U N C T I O N S   ===============================
// *_last_updated - is function for search updates creates by another client
//===========================================================================================

    public function orders($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        if($data && !is_null($flags) && ($flags & SSAPI_CONVERTER_WEB))
        {
            $this->web_json_encode_orders($data, ($flags & SSAPI_MULTI_QUERY));
        }

        $result = $this->send('orders', $search, $data, $flags, $options);
        if($result && !is_null($flags) && ($flags & SSAPI_CONVERTER_WEB)) {

            if(is_null($data)) {
                $result = $this->web_json_decode_orders($result, true);
            } elseif(isset($result['result'])) {
                $result['result'] = $this->web_json_decode_orders($result['result'], true);
            }
        }
        return $result;
    }
    public function orders_last_updated($from_date, $to_date, $flags = NULL, $options = NULL){
        $result = $this->last_updated('orders', $from_date, $to_date, $flags, $options);
        if($result && !is_null($flags) && ($flags & SSAPI_CONVERTER_WEB)) {
            $result = $this->web_json_decode_orders($result, true);
        }
        return $result;
    }
    public function order_items($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('orders.items', $search, $data, $flags, $options);
    }

    public function items_last_updated($from_date, $to_date, $flags = NULL, $options = NULL){
        $result = $this->last_updated('items', $from_date, $to_date, $flags, $options);
        if($result && !is_null($flags) && ($flags & SSAPI_CONVERTER_WEB)) {
            $result = $this->web_json_decode_fromitems($result);
//            foreach($result as $k => $v){ $result[$k] = $this->web_json_decode($v); }
        }
        return $result;
    }
    public function items_last_updated_minmax($from_date, $to_date){
        $result = $this->last_updated_minmax('items', $from_date, $to_date);
        return $result;
    }

    public function items($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        $result = $this->send('items', $search, $data, $flags, $options);
        if($result && !is_null($flags) && ($flags & SSAPI_CONVERTER_WEB)) {
            
            if(is_null($data)) {
                $result = $this->web_json_decode_fromitems($result);
            } elseif(isset($result['result'])) {
                $result['result'] = $this->web_json_decode_fromitems($result['result']);
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
        return $this->profiles_last_updated($from_date, $to_date, $flags = NULL, $options = NULL);
    }
    public function user_profiles($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->profiles($search = NULL, $data = NULL, $flags = NULL, $options = NULL);
    }
    public function profiles_last_updated($from_date, $to_date, $flags = NULL, $options = NULL){
        return $this->last_updated('profiles', $from_date, $to_date, $flags, $options);
    }
    public function profiles($search = NULL, $data = NULL, $flags = NULL, $options = NULL){
        return $this->send('profiles', $search, $data, $flags, $options);
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
};

?>