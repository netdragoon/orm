<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 3.2.12 (20150428)
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
