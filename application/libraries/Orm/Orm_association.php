<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 2.9 (20140611)
 */
class Orm_association {
	const TYPE_HAS_ONE = 'has_one';
	const TYPE_HAS_MANY = 'has_many';
	const TYPE_BELONGS_TO = 'belongs_to';
	
	public $field;
	public $value;
	public $type;
	
	public function __construct(array $data) {
		$this->field = $data['field'];
		$this->value = $data['value'];
		$this->type = $data['type'];
	}
}

/* End of file Orm_association.php */
/* Location: ./application/libraries/Orm_association.php */
