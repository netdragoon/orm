<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link https://github.com/maltyxx/orm
 */
class Orm_table extends Orm {

    public $name;

    public function __construct($name) {
        $this->name = $name;
    }

}

/* End of file Orm_table.php */
/* Location: ./application/libraries/Orm_table.php */
