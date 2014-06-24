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
	
	public $association_key;
	public $type;
	public $model;
	public $primary_key;
	public $foreign_key;
	public $value;
	
	public function __construct(array $data) {
		foreach ($data as $key => $value) {
            if (isset($this->{$key})) 
                $this->{$key} = $value;
        }
        
        if (empty($type->model))
            $this->model = $this->association_key.'_model';
        
        if (empty($type->primary_key))
            $this->primary_key = 'id';
        
        if (empty($type->foreign_key))
            $this->foreign_key = $this->association_key.'_id';
	}
    
    public function associated_model(Orm_model $model) {
        $this->value = $model->id;
    }
    
    public function create_model() {
        return new $this->model($this);
    }
}

/* End of file Orm_association.php */
/* Location: ./application/libraries/Orm_association.php */