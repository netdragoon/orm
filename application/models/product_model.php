<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class product_model extends Orm_model {

	public static $table = 'product';
	public static $primary_key = 'id';
	public static $fields = array(
	    'id' => 'int',
	    'price' => 'float'
	);
	public static $relation = array(
		'product_image' => array('has_one', 'product_image', 'produit_id', 'id'),
		'product_comment' => array('has_may', 'product_comment', 'produit_id', 'id')
	);
	
	//--START_PERSISTANT_CODE
	
	// Votre code
	
	//--END_PERSISTANT_CODE
}

// ------------------------------------------------------------------------

/* End of file product_model.php */
/* Location: ./application/models/product_model.php */