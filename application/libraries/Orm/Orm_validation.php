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
    const OPTION_TYPE_INT = 'int';
    const OPTION_TYPE_FLOAT = 'float';
    const OPTION_TYPE_EXCLUSION = 'exclusion';
    const OPTION_TYPE_INCLUSION = 'inclusion';
    const OPTION_TYPE_FORMAT = 'format';
    const OPTION_TYPE_LENGTH = 'length';
    const OPTION_TYPE_PRESENCE = 'presence';
    const OPTION_TYPE_CALLBACK = 'callback';
    const OPTION_MIN = 'min';
    const OPTION_MAX = 'max';
    const OPTION_LIST = 'list';
    const OPTION_MATCHER = 'matcher';
    const OPTION_CALLBACK = 'callback';

    public $field;
    public $type;
    public $min;
    public $max;
    public $list;
    public $matcher;
    public $callback;

    public function __construct(array $config) {
        foreach ($config as $config_key => $config_value) {
            $this->{$config_key} = $config_value;
        }
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

    private function _int($value) {
        return filter_var($value, FILTER_VALIDATE_INT);
    }

    private function _float($value) {
        return filter_var($value, FILTER_VALIDATE_FLOAT);
    }

    private function _exclusion($value) {
        if (!is_array($this->list))
            return FALSE;

        return !in_array($value, $this->list);
    }

    private function _inclusion($value) {
        if (!is_array($this->list))
            return FALSE;

        return in_array($value, $this->list);
    }

    private function _format($value) {
        if (empty($this->matcher))
            return FALSE;

        return preg_match($this->matcher, $value);
    }

    private function _length($value) {
        if (empty($value))
            return FALSE;

        $length = strlen($value);

        if (($this->min && $length < $this->min) || ($this->max && $length > $this->max)) {
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
        return call_user_func_array(array($this->callback), array($value));
    }
    
    public function validate(Orm_field $field) {
        if (call_user_func_array(array($this, "_$this->type"), array($field->value)) === FALSE)
            return FALSE;

        return TRUE;
    }

}

/* End of file Orm_validation.php */
/* Location: ./application/libraries/Orm_validation.php */
