<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

namespace kraken;

/**
 * @property integer $id
 * @method Orm_model artiste()
 */

class test_model extends Orm_model {

	public static $config = array(
		'table' => 'print',
		
		'primary_key' => 'id',
		
		'fields' => array(
			array('name' => 'id', 'type' => Orm_field::TYPE_INTEGER),
			array('name' => 'artist_id', 'type' => Orm_field::TYPE_INTEGER),
			array('name' => 'user_id', 'type' => Orm_field::TYPE_INTEGER),
			array('name' => 'title', 'type' => Orm_field::TYPE_STRING, 'allow_null' => TRUE),
			array('name' => 'email', 'type' => Orm_field::TYPE_STRING),
			array('name' => 'date', 'type' => Orm_field::TYPE_DATE, 'date_format' => parent::DATE_FOMAT_DATETIME, 'default_value' => parent::DATE_NOW),
		),
		
		'validations' => array(
			array('field' => 'email', 'type' => Orm_validation::TYPE_EMAIL)
		),
		
		'associations' => array('type' => parent::ASSOCIATION_BELONGSTO, 'model' => 'artiste', 'primary_key' => 'artiste_id', 'foreign_key' => 'artiste_id', 'association_key' => 'artiste')
	);

}