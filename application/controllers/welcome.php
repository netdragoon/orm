<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {

	public function __construct() {
		parent::__construct();
	}
    
    public function index() {
		$this->load->library('orm');
        
        $model_user = new dbd\user_model(1);
        
        $this->output->enable_profiler(TRUE);
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
