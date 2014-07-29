<?php
namespace dbd;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class user_model extends \Orm_model {

	public static $table = 'user';

	public static $primary_key = 'id';
    
	/**
	 * @property integer $id
	 * @property string $login
	 * @property string $password
	 * @property string $lastname
	 * @property string $firstname
	 * @property date $dateinsert
	 * @property date $dateupdate
	 */
	public static $fields = array(
		array('name' => 'id', 'type' => 'int'),
		array('name' => 'login', 'type' => 'string'),
		array('name' => 'password', 'type' => 'string'),
		array('name' => 'lastname', 'type' => 'string'),
		array('name' => 'firstname', 'type' => 'string'),
		array('name' => 'dateinsert', 'type' => 'date', 'date_format' => 'Y-m-d H:i:s', 'allow_null' => true, 'default_value' => 'now'),
		array('name' => 'dateupdate', 'type' => 'date', 'date_format' => 'Y-m-d H:i:s', 'allow_null' => true, 'default_value'=> 'now'),
	);

	/**
	 * @method user_group_model user_group() has_many
	 */
    public static $associations = array(
        array('association_key' => 'user_group', 'model' => 'user_group_model', 'type' => 'has_many', 'primary_key' => 'id', 'foreign_key' => 'user_id')
	);
        
    //--START_PERSISTANT_CODE
    
    public static $validations = array(
        array('field' => 'id', 'type' => 'int'),
        array('field' => 'login', 'type' => 'presence'),
        array('field' => 'password', 'type' => 'callback', 'callback' => '\dbd\user_model::check_password'),
        array('field' => 'lastname', 'type' => 'presence'),
        array('field' => 'dateinsert', 'type' => 'date'),
        array('field' => 'dateupdate', 'type' => 'date'),
	);
    
    public static function check_password($value, \Orm_validation &$validation) {
        if (empty($value)) {
            
            $validation->message = 'Le mot de passe ne doit pas être vide';
            //$validation->message = \Orm::$CI->lang->line('orm_validation_check_password');
            
            return FALSE;
        }
        
        return TRUE;
    }
    
    //--END_PERSISTANT_CODE
}

