<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 2.9 (20140611)
 */
class Orm_validation {

    const TYPE_EMAIL = 'email';
    const TYPE_URL = 'url';
    const TYPE_IP = 'ip';
    const TYPE_EXCLUSION = 'exclusion';
    const TYPE_FORMAT = 'format';
    const TYPE_INCLUSION = 'inclusion';
    const TYPE_LENGTH = 'length';
    const TYPE_PRESENCE = 'presence';
    const TYPE_CALLBACK = 'callback';
    const OPTION_MIN = 'min';
    const OPTION_MAX = 'max';
    const OPTION_LIST = 'list';
    const OPTION_MATCHER = 'matcher';

    public $field;
    public $value;
    public $type;

    public function __construct(array $data) {
        $this->field = $data['field'];
        $this->value = $data['value'];
        $this->type = $data['type'];
    }
}

/* End of file Orm_validation.php */
/* Location: ./application/libraries/Orm_validation.php */
