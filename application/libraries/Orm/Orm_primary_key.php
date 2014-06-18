<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 2.9 (20140611)
 */
class Orm_primary_key {
	
	public $field;
	public $value;
	
	public function __construct(array $data) {		
		$this->field = $data['field'];
		$this->value = intval($data['value']);
	}
}

/* End of file Orm_primary_key.php */
/* Location: ./application/libraries/Orm_primary_key.php */
