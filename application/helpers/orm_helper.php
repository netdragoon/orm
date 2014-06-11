<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Permet le chargement automatique des modèles
 * @param string $class
 */
function orm_autoload($class) {
	if (strstr($class, '_model') !== FALSE) {
		$file_path = FCPATH.APPPATH.'models/'.$class.'.php';
								
		if (is_file($file_path))
			include_once($file_path);
	}
}

spl_autoload_register('orm_autoload');

// ------------------------------------------------------------------------

/* End of file orm_helper.php */
/* Location: ./application/helpers/orm_helper.php */