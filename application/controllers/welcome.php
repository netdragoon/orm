<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {
	
	public function index() {
		$this->load->library('orm');
		
		new kraken\project_model();
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */