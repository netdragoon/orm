<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class product_model extends Orm {

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
}

// ------------------------------------------------------------------------

/* End of file product_model.php */
/* Location: ./application/models/product_model.php */