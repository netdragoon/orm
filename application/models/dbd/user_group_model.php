<?php
namespace dbd;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class user_group_model extends \Orm_model {

	public static $table = 'user_group';

	public static $primary_key = 'id';
    
	/**
	 * @property integer $id
	 * @property string $name
	 * @property date $dateinsert
	 * @property date $dateupdate
	 */
	public static $fields = array(
		array('name' => 'id', 'type' => 'int'),
		array('name' => 'user_id', 'type' => 'int'),
		array('name' => 'name', 'type' => 'string'),
		array('name' => 'dateinsert', 'type' => 'date', 'date_format' => 'Y-m-d H:i:s', 'allow_null' => true, 'default_value' => 'now'),
		array('name' => 'dateupdate', 'type' => 'date', 'date_format' => 'Y-m-d H:i:s', 'allow_null' => true, 'default_value' => 'now'),
	);

	/**
	 * @method dbd\user_model user() belongs_to
	 */
    public static $associations = array(
		array('association_key' => 'user', 'model' => 'user_model', 'type' => 'belongs_to', 'primary_key' => 'id', 'foreign_key' => 'user_id')
	);
     
    public static $validations = array(
        array('field' => 'id', 'type' => 'int'),
        array('field' => 'user_id', 'type' => 'int'),
        array('field' => 'name', 'type' => 'presence'),
        array('field' => 'dateinsert', 'type' => 'date'),
        array('field' => 'dateupdate', 'type' => 'date'),
	);
}

