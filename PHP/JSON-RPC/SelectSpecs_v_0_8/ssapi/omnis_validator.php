<?php
class SS_Items_Validator
{
    private $errors = array();
    private $need_to_stop = false;
    private $line = array();
    private $key = 0;
    private $options_ids = array();
    public function __constuct() {

    }

    // Set line for validate
    public function set_line(&$line, &$key) {
        $this->line = $line;
        $this->key = $key;
    }

    // Return array of errors, if found
    public function errors() {
        return $this->errors;
    }
    // Return last error
    public function last_error() {
        // If first error, cursor go to next row
        if (count($this->errors) == 1) $res = "\n"; else $res = '';
        return $res.$this->errors[count($this->errors)-1];
    }

    // Return bool for stop processing or continue
    public function need_to_stop() {
        return $this->need_to_stop;
    }

    // Add new error to array
    private function add_error($error, $critical = false) {
        if ($critical) {
            $this->need_to_stop = true;
            $this->errors[] = ' ERROR. '.$error."\n";
            $this->errors[] = " Import stopped.\n";
        } else {
            $this->errors[] = ' ERROR line '.($this->key+1).', sku '.$this->line['sku'].'. '.$error.' Line skipped.'."\n";
        }
    }
    // Add new warning
    private function add_warning($error) {
        $this->errors[] = ' WARNING line '.($this->key+1).', sku '.$this->line['sku'].'. '.$error." \n";
    }

    // *************************************
    // Send report result
    // *************************************
    public function send_report() {
        $text = 'Items import finished: '.date('d.m.Y G:i', time())."\n";
        if (count($this->errors) > 0) {
            foreach($this->errors() as $error) {
                $text = $text . $error;
            }
        } else {
            $text = $text . 'Errors not found.';
        }
        require_once(dirname(__FILE__) . "/../config/email.php");
        // We get $config['report_emails'] from email config
        mail(implode(',', $config['report_emails']), "Items Import result", $text);
    }

    // *************************************
    // Validators
    // *************************************
    public function validate_line(&$line, &$key) {
        // Set line for validator. This line will validate.
        $this->set_line($line, $key);

        // Validating field on count line
        if (!$this->validate_rows_count()) {
            echo $this->last_error();
            return false;
        }
        // Validating on duplicate line
        if (!$this->validate_on_duplicate_options()) {
            echo $this->last_error();
            return false;
        }

        // Validating on correct field parameters
        if (!$this->validate_parameters()) {
            echo $this->last_error();
            return false;
        }

        // Only warning
        if ($this->line['price'] == 0 || $this->line['price'] == '0.00') {
            $this->add_warning('price is "0"');
            echo $this->last_error();
        }

        return true;
    }

    // Validate on load from file
    public function validate_load($file) {
        if (!file_exists($file)) {
            $this->add_error('Import file '.$file.' not found', true);
            return;
        }

        $file_handle = @fopen($file,"r");
        if (!$file_handle) {
            $this->add_error("Can't open file $file!", true);
            return;
        }
        fclose($file_handle);
    }
    // Validate on count of fields
    public function validate_rows_count() {
        if (count($this->line) != 31) {
            $this->add_error('Incorrect columns count, '.count($this->line).'.');
            return false;
        }
        return true;
    }

    // Validate on duplicate options. 0 - for discontinued option
    public function validate_on_duplicate_options() {
        $key = $this->line['item_id'].$this->line['option_order'];
        if ($this->line['option_order'] != 0) {
            if (isset($this->options_ids[$key])) {
                $this->add_error('Duplicate option.');
                return false;
            }
        }
        $this->options_ids[$key] = ' ';
        return true;
    }

    // Validate on required fields
    private function field_is_exists($str) {
        if (is_array($str)) $fields = $str;
        else $fields[0] = $str;

        foreach ($fields as $field) {
            if (!isset($this->line[$field])) {
                throw new Exception('Required field "'.$field.'" not found.');
            }
        }
    }

    // Validate on numeric fields
    private function field_is_numeric($str) {
        if (is_array($str)) $fields = $str;
        else $fields[0] = $str;

        foreach ($fields as $field) {
            if (!is_numeric($this->line[$field])) {
                throw new Exception('Field "'.$field.'" has parameter "'.$this->line[$field].'" is not numeric.');
            }
        }
    }
    // Validate on not empty fields
    private function field_is_not_empty($str) {
        if (is_array($str)) $fields = $str;
        else $fields[0] = $str;

        foreach ($fields as $field) {
            if ($this->line[$field] == '') {
                throw new Exception('Required field "'.$field.'" is empty.');
            }
        }
    }

    public function validate_parameters() {
        try {
            // This fields will be required
            $this->field_is_exists(
                array(
                    'sku',
                    'item_id',
                    'tab',
                    'base_curve',
                    'supplier_name',
                    'model_name',
                    'option_order',
                    'option_name',
                    'price',
                    'price_old',
                    'prices_domain',
                    'option_description',
                    'rrp',
                    'supplier_description',
                    'warranty_period',
                    'pd',
                    'category_names',
                    'featured',
                    'supp_name',
                    'supplier_name',
                    'item_info',
                    'item_added',
                    'weight',
                    'frame_sizes',
                    'is_modified',
                    'product_information',
                    'no_option_images',
                    'no_large_image'
                )
            );
            // This fields will be numeric
            $this->field_is_numeric(
                array(
                    'sku',
                    'item_id',
                    'base_curve',
                    'price',
                    'rrp',
                    'pd',
                    'price_old',
                    'weight',
                    'option_order'
                )
            );
            // This fields can't be empty
            $this->field_is_not_empty(
                array(
                    'sku',
                    'item_id',
                    'supplier_name',
                    'model_name'
                )
            );

            // Over validations
            if (($this->line['item_added'] != 'NULL') && (! strtotime($this->line['item_added']) || (strtotime($this->line['item_added']) > time()))) {
                throw new Exception('Field "item_added" has parameter "'.$this->line['item_added'].'".');
            }

        }
        catch (Exception $e) {
            $this->add_error($e->getMessage());
            return false;
        }
        return true;
    }
}