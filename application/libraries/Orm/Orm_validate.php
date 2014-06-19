<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 2.9 (20140611)
 */
class Orm_validate {

    public function __construct(Orm_field $field, Orm_validation $validation) {
    }

    protected function email($config, $value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    protected function url($config, $value) {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    protected function ip($config, $value) {
        return filter_var($value, FILTER_VALIDATE_IP);
    }

    protected function exclusion($config, $value) {
        return !in_array($value, $config);
    }

    protected function format($config, $value) {
        preg_match($config, $value);
    }

    protected function inclusion($config, $value) {
        return in_array($value, $config);
    }

    protected function length($config, $value) {
        if (empty($value))
            return FALSE;

        $length = strlen($value);

        if (($length < $config['min']) || ($length > $config['max'])) {
            return FALSE;
        } else {
            return $value;
        }
    }

    protected function presence($config, $value) {
        if (empty($value) && $value !== 0) {
            return FALSE;
        } else {
            return $value;
        }
    }

    protected function callback($config, $value) {
        return all_user_func($value, $config);
    }

}

/* End of file Orm_validation.php */
/* Location: ./application/libraries/Orm_validation.php */
