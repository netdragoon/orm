<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 2.9 (20140611)
 */
class Orm_validation {

    const OPTION_TYPE_EMAIL = 'email';
    const OPTION_TYPE_URL = 'url';
    const OPTION_TYPE_IP = 'ip';
    const OPTION_TYPE_EXCLUSION = 'exclusion';
    const OPTION_TYPE_FORMAT = 'format';
    const OPTION_TYPE_INCLUSION = 'inclusion';
    const OPTION_TYPE_LENGTH = 'length';
    const OPTION_TYPE_PRESENCE = 'presence';
    const OPTION_TYPE_CALLBACK = 'callback';
    const OPTION_MIN = 'min';
    const OPTION_MAX = 'max';
    const OPTION_LIST = 'list';
    const OPTION_MATCHER = 'matcher';

    public $field;
    public $type;
    public $min;
    public $max;
    public $list;
    public $matcher;

    public function __construct(array $config, $value) {
        foreach ($config as $config_key => $config_value) {
            if (isset($this->{$config_key})) {
                $this->{$config_key} = $config_value;
            }
        }
        
        $this->value = $value;
    }
    
    private function _email($value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    private function _url($value) {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    private function _ip($value) {
        return filter_var($value, FILTER_VALIDATE_IP);
    }

    private function _exclusion($value) {
        return !in_array($value, $config);
    }

    private function _format($value) {
        preg_match($config, $value);
    }

    private function _inclusion($value) {
        return in_array($value, $config);
    }

    private function _length($value) {
        if (empty($value))
            return FALSE;

        $length = strlen($value);

        if (($length < $config['min']) || ($length > $config['max'])) {
            return FALSE;
        } else {
            return $value;
        }
    }

    private function _presence($value) {
        if (empty($value) && $value !== 0) {
            return FALSE;
        } else {
            return $value;
        }
    }

    private function _callback($value) {
        return all_user_func($value, $config);
    }
    
    public function validate(Orm_field $field) {
        $method = "_$this->type";
        
        if ($this->$method($field->value) === FALSE) {
            return FALSE;
        }
        
        return TRUE;
        
    }
}

/* End of file Orm_validation.php */
/* Location: ./application/libraries/Orm_validation.php */
