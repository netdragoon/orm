<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Test extends CI_Controller {
	
	public function __construct() {
		parent::__construct();
		
		$this->load->library('orm');
	}
	
	public function index() {
		
		// Exemple 1
		$product = new product_model(1);			
		$product->price = 10.10;
		$product->save();
		
		// Exemple 2
		$product = new product_model();
		$product->where('id', 1)->find_one();
		$product->price = 10.10;
		$product->save();
		
		// Exemple 3
		$product = new product_model(3);			
		$image = $product->product_image->find();
		echo $image->file;
		
		// Exemple 4
		$product = new product_model(3);			
		$comments = $product->product_comment->find();
		
		foreach ($comments as $comment) {
			echo $comment->title;
		}
	}

}

/* End of file test.php */
/* Location: ./application/controllers/test.php */