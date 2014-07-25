<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 3.1.1 (20140725)
 */

/**
 * Permet le chargement automatique des modèles
 * @param string $class
 */
function orm_autoload($class) {
	if (strstr($class, '_model') !== FALSE) {
		$file_path = str_replace('\\', '/', FCPATH.APPPATH.'models/'.$class.'.php');
        
		if (is_file($file_path))
			include_once($file_path);
	}
}

spl_autoload_register('orm_autoload');

// ------------------------------------------------------------------------

/* End of file orm_helper.php */
/* Location: ./application/helpers/orm_helper.php */