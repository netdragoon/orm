<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link https://github.com/maltyxx/orm
 */
class Orm_primary_key extends Orm {

    public $name;
    public $value;

    public function __construct($name, $value) {
        $this->name = $name;
        $this->value = (int) $value;
    }

}

/* End of file Orm_primary_key.php */
/* Location: ./application/libraries/Orm_primary_key.php */
