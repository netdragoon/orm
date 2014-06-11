<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 2.9 (20140611)
 */
class Orm_field {
	const TYPE_INTEGER = 'int';
	const TYPE_INT = self::TYPE_INTEGER;
	const TYPE_FLOAT = 'float';
	const TYPE_DOUBLE = self::TYPE_FLOAT;
	const TYPE_STRING = 'string';
	const TYPE_DATE = self::TYPE_STRING;
	const TYPE_DATETIME = self::TYPE_STRING;
	
	const OPTION_NULL = 'NULL';
	const OPTION_ENCRYPT = 'encrypt';
	const OPTION_BINARY = 'binary';
	const OPTION_VECTOR = 'vector';
	
	public $name;
	public $value;
	public $type;
	
	public function __construct(array $data) {
		$this->name = $data['name'];
		$this->value = $data['value'];
		$this->type = $data['type'];
	}
	
	public function get_options() {
		return explode('|', $this->type);
	}
		
	public function is_null() {		
		return in_array(self::OPTION_NULL, $this->get_options());
	}
	
	public function is_encrypt() {		
		return in_array(self::OPTION_ENCRYPT, $this->get_options());
	}
	
	public function is_binary() {		
		return in_array(self::OPTION_BINARY, $this->get_options());
	}
	
	public function is_vector() {		
		return ($this->name === self::OPTION_VECTOR);
	}
	
	public function get_type() {
		$type = $this->get_options();
		
		if (in_array(self::TYPE_INTEGER, $type)) {
			return self::TYPE_INTEGER;
		} else if (in_array(self::TYPE_FLOAT, $type)) {
			return self::TYPE_FLOAT;
		} else {
			return self::TYPE_STRING;
		}
	}
	
	public function cast() {
		if ($this->is_null() && empty($this->value)) {
			settype($this->value, self::OPTION_NULL);
			return;
		} 
		
		switch (strtolower($this->type)) {
			case self::TYPE_INTEGER:
				settype($this->value, self::TYPE_INTEGER);
				break;
			case self::TYPE_FLOAT:
				settype($this->value, self::TYPE_FLOAT);
				break;
			case self::TYPE_STRING:
			default:
				settype($this->value, self::TYPE_STRING);
		}
	}
}

/* End of file Orm_field.php */
/* Location: ./application/libraries/Orm_field.php */
