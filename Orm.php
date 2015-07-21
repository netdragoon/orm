<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require(__DIR__.'/Orm_association.php');
require(__DIR__.'/Orm_field.php');
require(__DIR__.'/Orm_model.php');
require(__DIR__.'/Orm_primary_key.php');
require(__DIR__.'/Orm_table.php');
require(__DIR__.'/Orm_validation.php');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link https://github.com/maltyxx/orm
 */
class Orm {

    /**
     * Instance de Codeigniter
     * @var object $CI
     */
    protected static $CI = NULL;
    
    /**
     * Version de l'ORM
     * @var string 
     */
    protected $version = '3.3.4';

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
    function __construct(array $config = array()) {
        // Initialise la configuration, si elle existe
        if (isset($config['orm']))
            self::$config = array_merge(self::$config, $config['orm']);

        // Premier chargement de L'ORM
        if (self::$CI === NULL) {
            // Charge l'instance de CodeIgniter
            self::$CI = & get_instance();
            
            // Charge le fichier langue
            self::$CI->load->language('orm');

            // Si la clé de cryptage est vide, on désactive le cryptage
            if (self::$config['encryption_enable'] && empty(self::$config['encryption_key']))
                self::$config['encryption_enable'] = FALSE;

            // Charge l'autoloader de L'ORM
            if (self::$config['autoloadmodel'])
                self::$CI->load->helper('orm');

            // Si le cryptage est actif charge les éléments indispensable au cryptage
            if (self::$config['encryption_enable']) {
                self::$CI->load->helper('string');
            }
        }
    }
    
    /**
     * Retourne la version de l'ORM
     * @return string
     */
    public function get_version() {
        return $this->version;
    }
}

/* End of file Orm.php */
/* Location: ./application/libraries/Orm.php */
