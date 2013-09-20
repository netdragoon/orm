<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 1.0 (20130919)
 */
class Orm {

	/**
	 * Instance de Codeigniter
	 * @var object $CI
	 */
	private static $CI = NULL;

	/**
	 * Status du cache
	 * @var boolean $cache
	 */
	private static $cache = FALSE;

	/**
	 * Temps de vie du cache par défaut
	 * @var int $tts
	 */
	private static $tts = 3600;

	/**
	 * Constructeur
	 * @param NULL|int|array $primary_key 
	 * @return object|
	 */
	function __construct($primary_key = NULL) {

		if (get_called_class() == 'Orm') {

			if (is_null(self::$CI)) {
				self::$CI = & get_instance();
				self::$CI->load->config('orm');
				self::$CI->load->helper('orm');

				self::$cache = self::$CI->config->item('cache', 'orm');
				self::$tts = self::$CI->config->item('tts', 'orm');
			}
			
			return;
		}

		$this->objects();

		if (is_null($primary_key))
			return;

		if (is_numeric($primary_key))
			return $this->where(static::$primary_key, (int) $primary_key)->find_one();

		if (is_array($primary_key))
			$this->relation($primary_key);
	}

	/**
	 * Créer les variables dans le modèle
	 * @return
	 */
	protected function objects() {
		if (count(get_object_vars($this)) > 0)
			return;

		foreach (static::$fields as $field => $type)
			$this->$field = NULL;
	}

	/**
	 * Cast les variables du modèle
	 * @param NULL|string $data
	 * @return \Orm
	 */
	protected function data($data = NULL) {
		if (!is_array($data))
			return;

		foreach ($data as $field => $value) {
			$this->$field = $value;

			$type = explode('|', static::$fields[$field]);

			if (empty($value) && in_array('NULL', $type)) {
				settype($this->$field, 'NULL');
				continue;
			}

			switch (strtolower($type[0])) {
				case 'int':
				case 'integer':
					settype($this->$field, 'integer');
					break;
				case 'double':
				case 'float':
					settype($this->$field, 'float');
					break;
				case 'date':
				case 'datetime':
				//$this->$field = new DateTime($this->$field);
				//break;
				case 'string':
				default:
					settype($this->$field, 'string');
			}
		}

		return $this;
	}
	
	/**
	 * Distinct
	 * @param boolean $val
	 * @return \Orm
	 */
	public function distinct($val = TRUE) {
		self::$CI->db->distinct($val);

		return $this;
	}
	
	/**
	 * Where
	 * @param mixe $key
	 * @param NULL|string|int|float $value
	 * @param boolean $escape
	 * @return \Orm
	 */
	public function where($key, $value = NULL, $escape = TRUE) {
		self::$CI->db->where($key, $value, $escape);

		return $this;
	}
	
	/**
	 * Like
	 * @param mixe $field
	 * @param string $match
	 * @param string $side
	 * @return \Orm
	 */
	public function like($field, $match = '', $side = 'both') {
		self::$CI->db->like($field, $match, $side);

		return $this;
	}
	
	/**
	 * Group by
	 * @param string $by
	 * @return \Orm
	 */
	public function group_by($by) {
		self::$CI->db->group_by($by);

		return $this;
	}
	
	/**
	 * Having
	 * @param string $key
	 * @param string $value
	 * @param boolean $escape
	 * @return \Orm
	 */
	public function having($key, $value = '', $escape = TRUE) {
		self::$CI->db->having($key, $value, $escape);

		return $this;
	}
	
	/**
	 * Order by
	 * @param string $orderby
	 * @param string $direction
	 * @return \Orm
	 */
	public function order_by($orderby, $direction = '') {
		self::$CI->db->order_by($orderby, $direction);

		return $this;
	}
	
	/**
	 * Lmit
	 * @param string $value
	 * @param string $offset
	 * @return \Orm
	 */
	public function limit($value, $offset = '') {
		self::$CI->db->limit($value, $offset);

		return $this;
	}
	
	/**
	 * Compte les résultats
	 * @return int
	 */
	public function count() {
		return (int) self::$CI->db->count_all_results(static::$table);
	}
	
	/**
	 * Recherche en base de donnée
	 * @return array
	 */
	protected function _data_find() {
		self::$CI->db->from(static::$table);
		
		// Si le cache est activé
		if (self::$cache) {
			$cache_id = static::$table;
			$cache_key = md5(self::$CI->db->_compile_select());
			
			// Vérifie si le cache existe
			if ( ! $data = self::$CI->cache->get($cache_id) OR ! isset($data[$cache_key])) {
				$data = (is_array($data)) ? $data : array();

				$data[$cache_key] = self::$CI->db->get()->result_array();

				self::$CI->cache->save($cache_id, $data, self::$tts);
			}
			
			self::$CI->db->_reset_select();
			
			return $data[$cache_key];
		}
		
		return self::$CI->db->get()->result_array();
	}
	
	/**
	 * Cherche plusieurs objets
	 * @return boolean|array
	 */
	public function find() {
		$objects = array();

		$data = $this->_data_find();

		if (is_numeric($data))
			return array($data);

		if (empty($data))
			return FALSE;

		foreach ($data as $value)
			$objects[] = clone $this->data($value);

		return $objects;
	}
	
	/**
	 * Cherche un objet
	 * @return boolean|objet
	 */
	public function find_one() {
		self::$CI->db->limit(1);

		$data = $this->find();

		return (isset($data[0])) ? $data[0] : FALSE;
	}
	
	/**
	 * Sauvegarde un objet
	 * @param boolean $force_insert
	 * @return boolean
	 */
	public function save($force_insert = FALSE) {
		if (count(get_object_vars($this)) === 0)
			return FALSE;

		$this->data(get_object_vars($this));

		if (self::$cache)
			self::$CI->cache->delete(static::$table);

		if (isset($this->{static::$primary_key}) && !empty($this->{static::$primary_key}) && $force_insert === FALSE) {
			return self::$CI->db
				->where(static::$primary_key, $this->{static::$primary_key})
				->update(static::$table, get_object_vars($this));
		} else {
			self::$CI->db
				->insert(static::$table, get_object_vars($this));

			return self::$CI->db->insert_id();
		}
	}
	
	/**
	 * Supprime un objet
	 * @return boolean
	 */
	public function remove() {
		if (count(get_object_vars($this)) > 0)
			return FALSE;

		if (!isset($this->{static::$primary_key}) || empty($this->{static::$primary_key}))
			return FALSE;

		if (self::$cache)
			self::$CI->cache->delete(static::$table);

		return self::$CI->db
			->where(static::$primary_key, $this->{static::$primary_key})
			->delete(static::$table, get_object_vars($this));
	}

	/**
	 * Détruit les variables d'un objet
	 * @param type $all
	 */
	public function clear($all = FALSE) {
		foreach (get_object_vars($this) as $field) {
			if ($all)
				unset($this->$field);
			else
				$this->$field = NULL;
		}

		if ($all)
			static::$fields = array();
	}
	
	/**
	 * Passage d'objets
	 * @param type $get
	 * @return boolean|\class
	 */
	public function __get($get) {
		if (!property_exists(static::$table.'_model', 'relations'))
			return FALSE;
		
		if (!isset(static::$relations[$get][0]))
			return FALSE;

		$class = static::$relations[$get][1].'_model';

		return new $class(array(
		    static::$relations[$get][0], // Relation
		    static::$relations[$get][2], // Champ
		    $this->{static::$relations[$get][3]} // Valeur
		));
	}

	/**
	 * Relations entrent objets
	 * @param string $options
	 * @return \Orm
	 */
	public function relation($options) {
		if ( ! isset($options[0]))
			return;

		switch ($options[0]) {
			// Relation entre un parent et un enfant
			case 'has_one':
				self::$CI->db->where($options[1], $options[2])->limit(1);
				break;
			// Relation entre un parent et des enfants
			case 'has_many':
				self::$CI->db->where($options[1], $options[2]);
				break;
			// Relation entre un enfant et un parent
			case 'belongs_to':
				self::$CI->db->where($options[1], $options[2]);
				break;
		}

		return $this;
	}

}

/* End of file Orm.php */
/* Location: ./application/libraries/Orm.php */