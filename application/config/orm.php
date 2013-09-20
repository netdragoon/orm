<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
|--------------------------------------------------------------------------
| Model
|--------------------------------------------------------------------------
*/

// Utilisation du cache APC, il fait un MD5 de la requete pour nom de variable
// utilisation intelligente car APC stock par hits et du coup il ne garde que le hit machine !
// tts : en heure

$config['orm'] = array(
	'cache' => FALSE,
	'tts' => 3600
);

/* End of file orm.php */
/* Location: ./application/config/orm.php */