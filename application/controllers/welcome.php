<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {
	
	public function index() {
		$this->load->library('orm');
		
		$test = new kraken\project_model();
        
        var_dump($test->where('id', 1)->find_one());
        
        $this->output->enable_profiler(TRUE);
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */