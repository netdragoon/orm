<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class product_model extends Orm {

	public static $table = 'product';
	public static $primary_key = 'id';
	public static $fields = array(
	    'id' => 'int',
	    'price' => 'float'
	);
}

// ------------------------------------------------------------------------

/* End of file product_model.php */
/* Location: ./application/models/product_model.php */