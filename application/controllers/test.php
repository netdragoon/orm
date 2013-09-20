<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Test extends CI_Controller {
	
	public function __construct() {
		parent::__construct();
		
		$this->load->library('orm');
	}
	
	public function index() {
				
		$product = new product_model();	
			
		$products = $product->find();
			
		foreach ($products as $product) {
			$product->price = 10.10;
			$product->save();
		}
	}

}

/* End of file test.php */
/* Location: ./application/controllers/test.php */