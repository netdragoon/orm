<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 2.9 (20140611)
 */
class Orm {
	/**
	 * Instance de Codeigniter
	 * @var object $CI
	 */
	protected static $CI = NULL;
	
	/**
	 * Configuration de l'ORM
	 * @var array 
	 */
	protected static $config = array(
		'cache' => FALSE,
		'tts' => 3600,
		'autoloadmodel' => FALSE,
		'binary_enable' => FALSE, // MySQL 5.6 minimum
		'encryption_enable' => FALSE, // MySQL 5.6 minimum
		'encryption_key' => NULL // MySQL 5.6 minimum
	);

	/**
	 * Constructeur
	 * @param array $config
	 */
	function __construct(array $config) {
		// Initialise la configuration, si elle existe
		if (isset($config['orm']))
			self::$config = array_merge(self::$config, $config['orm']);
		
		// Premier chargement de L'ORM
		if (self::$CI === NULL) {
			// Charge l'instance de CodeIgniter
			self::$CI = & get_instance();
			
			// Si la clé de cryptage est vide, on désactive le cryptage
			if (self::$config['encryption_enable'] && empty(self::$config['encryption_key']))
				self::$config['encryption_enable'] = FALSE;
			
			// Charge l'autoloader de L'ORM
			if (self::$config['autoloadmodel'])
				self::$CI->load->helper('orm');
			
			// Si le cryptage est actif charge les éléments indispensable au cryptage
			if (self::$config['encryption_enable']) {
				self::$CI->load->helper('string');
				self::$CI->db->query("SET @@session.block_encryption_mode = 'aes-256-cbc';");
			}
		}
	}
}

/* End of file Orm.php */
/* Location: ./application/libraries/Orm.php */
