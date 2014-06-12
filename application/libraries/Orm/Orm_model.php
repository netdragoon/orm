<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 2.9 (20140611)
 */
final class Orm_model extends Orm {
	/**
	 * Constructeur
	 * @param NULL|int|array $data
	 * @return object|
	 */
	function __construct($data = NULL) {
		
		// Initialise le contruct parent			
		parent::__construct((is_array($data)) ? $data : array());
				
		// Si la variable $data est un entier, c'est une clé primaire
		if (is_numeric($data)) {
			return $this->where(static::$primary_key, (int) $data)->find_one();
			
		// Si la variable $data est une instance de la classe Orm_association
		} else if ($data instanceof Orm_association) {
			$this->association($data);
		}
	}
		
	private function _output() {
		$output = array();

		foreach (static::$fields as $name => $type) {
			
			$field = new Orm_field(array(
				'name' => $name,
				'value' => $this->{$name},
				'type' => $type
			));
			
			if (parent::$config['encryption_enable'] && $field->is_encrypt()) {
				$output[] = array("CONVERT(AES_DECRYPT(FROM_BASE64(`".$field->name."`), UNHEX('".parent::$config['encryption_key']."'), UNHEX(`vector`)) USING 'utf8') AS `".$field->name."`", FALSE);
				
			} else if (parent::$config['binary_enable'] && $field->is_binary()) {
				$output[] = array("TO_BASE64(`".$field->name."`) AS `".$field->name."`", FALSE);
				
			} else {
				$output[] = array($field->name, NULL);
			}
		}
		
		return $output;
	}

	private function _input(array $data) {
		$input = array();
		$vector_value = NULL;
		
		if (parent::$config['encryption_enable'])
			$vector_value = ( ! empty($data['vector'])) ? $data['vector'] : random_string('unique');

		// on boucle sur tous les champs de la table
		foreach ($data as $name => $value) {
			
			$field = new Orm_field(array(
				'name' => $name,
				'value' => $value,
				'type' => static::$fields[$name]
			));
						
			// Si c'est un champ qu'on doit crypter
			if (parent::$config['encryption_enable'] && $field->is_encrypt()) {
				$input[] = array($field->name, "TO_BASE64(AES_ENCRYPT('".parent::$CI->db->escape_str($field->value)."', UNHEX('".parent::$config['encryption_key']."'), UNHEX('".$vector_value."')))", FALSE);
			
			// Si c'est un champ vecteur
			} else if (parent::$config['encryption_enable'] && $field->is_vector()) {
				$input[] = array($field->name, $vector_value, TRUE);
				
			// Si c'est un champ binaire
			} else if (parent::$config['binary_enable'] && $field->is_binary()) {
				$input[] = array($field->name, "FROM_BASE64('".parent::$CI->db->escape_str($field->value)."')", FALSE);
			
			// Par défaut
			} else {
				$input[] = array($field->name, $field->value, TRUE);
			}
		}
		
		return $input;
	}
	
	/**
	 * Caste une variable
	 * @param mixe $name
	 * @param mixe $value
	 */
	public function __set($name, $value) {
		$this->_cast_field(new Orm_field(array(
			'name' => $name,
			'value' => $value,
			'type' => static::$fields[$name]
		)));
    }
	
	/**
	 * Caste un tableau de variables
	 * @param NULL|string $data
	 * @return \Orm_model
	 */
	private function _cast_fields(array $data) {
		foreach ($data as $name => $value) {
			$this->_cast_field(new Orm_field(array(
				'name' => $name,
				'value' => $value,
				'type' => static::$fields[$name]
			)));
		}

		return $this;
	}
	
	private function _cast_field(Orm_field $field) {
		// Caste l'objet
		$field->cast();
		
		// Met a jour la valeur de l'objet
		$this->{$field->name} = $field->value;
	}
	
	/**
	 * Where
	 * @param mixe $key
	 * @param NULL|string|int|float $value
	 * @param boolean $escape
	 * @return \Orm_model
	 */
	public function where($key, $value = NULL, $escape = TRUE) {
		parent::$CI->db->where($key, $value, $escape);

		return $this;
	}
	
	/**
	 * Where
	 * @param mixe $key
	 * @param NULL|string|int|float $value
	 * @param boolean $escape
	 * @return \Orm_model
	 */
	public function where_in($key = NULL, $values = NULL) {
		parent::$CI->db->where_in($key, $values);

		return $this;
	}

	/**
	 * Like
	 * @param mixe $field
	 * @param string $match
	 * @param string $side
	 * @return \Orm_model
	 */
	public function like($field, $match = '', $side = 'both') {
		parent::$CI->db->like($field, $match, $side);

		return $this;
	}

	/**
	 * Group by
	 * @param string $by
	 * @return \Orm_model
	 */
	public function group_by($by) {
		parent::$CI->db->group_by($by);

		return $this;
	}

	/**
	 * Having
	 * @param string $key
	 * @param string $value
	 * @param boolean $escape
	 * @return \Orm_model
	 */
	public function having($key, $value = '', $escape = TRUE) {
		parent::$CI->db->having($key, $value, $escape);

		return $this;
	}

	/**
	 * Order by
	 * @param string $orderby
	 * @param string $direction
	 * @return \Orm_model
	 */
	public function order_by($orderby, $direction = '') {
		parent::$CI->db->order_by($orderby, $direction);

		return $this;
	}

	/**
	 * Lmit
	 * @param string $value
	 * @param string $offset
	 * @return \Orm_model
	 */
	public function limit($value, $offset = '') {
		parent::$CI->db->limit($value, $offset);

		return $this;
	}

	/**
	 * Compte les résultats
	 * @return int
	 */
	public function count() {
		return (int) parent::$CI->db->count_all_results(static::$table);
	}

	/**
	 * Recherche en base de donnée
	 * @return array
	 */
	protected function _data_find() {

		foreach ($this->_output() as $select)
			parent::$CI->db->select($select[0], $select[1]);

		parent::$CI->db->from(static::$table);

		// Si le cache est activé
		if (parent::$config['cache']) {
			$cache_id = static::$table;
			$cache_key = md5(parent::$CI->db->_compile_select());

			// Vérifie si le cache existe
			if (!$data = parent::$CI->cache->get($cache_id) OR ! isset($data[$cache_key])) {
				$data = (is_array($data)) ? $data : array();

				$data[$cache_key] = parent::$CI->db->get()->result_array();

				parent::$CI->cache->save($cache_id, $data, parent::$config['tts']);
			}

			// Vide la requete
			parent::$CI->db->_reset_select();

			// Retoune les résultats en cache
			return $data[$cache_key];
		}

		// Retourne les résultats
		return parent::$CI->db->get()->result_array();
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
			$objects[] = clone $this->_cast_fields($value);

		return $objects;
	}

	/**
	 * Cherche un objet
	 * @return boolean|objet
	 */
	public function find_one() {
		parent::$CI->db->limit(1);

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
		
		if (parent::$config['cache'])
			parent::$CI->cache->delete(static::$table);

		parent::$CI->db->from(static::$table);

		foreach ($this->_input(get_object_vars($this)) as $set)
			parent::$CI->db->set($set[0], $set[1], $set[2]);

		if (isset($this->{static::$primary_key}) && !empty($this->{static::$primary_key}) && $force_insert === FALSE) {
			return parent::$CI->db->where(static::$primary_key, $this->{static::$primary_key})->update();
		} else {
			parent::$CI->db->insert();
			return $this->{static::$primary_key} = parent::$CI->db->insert_id();
		}
	}

	/**
	 * Supprime un objet
	 * @return boolean
	 */
	public function remove() {
		if (count(get_object_vars($this)) == 0)
			return FALSE;

		if ( ! isset($this->{static::$primary_key}) || empty($this->{static::$primary_key}))
			return FALSE;

		if (parent::$config['cache'])
			parent::$CI->cache->delete(static::$table);

		return parent::$CI->db
				->where(static::$primary_key, $this->{static::$primary_key})
				->delete(static::$table);
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
	 * Passage d'objets en appelant la methode du nom de la relation
	 * On peut passer en parametre des arguments pour filtrer le retour d'object
	 * @param type $get
	 * @return boolean|\Orm_model
	 */
	public function __call($name, $argument) {
		if ( ! property_exists(static::$table.'_model', 'relations'))
			return FALSE;

		if ( ! isset(static::$relations[$name][0]))
			return FALSE;

		$class_model = static::$relations[$name][1].'_model';
		
		return new $class_model(new Orm_association(array(
			'field' => static::$relations[$name][2],
			'value' => $this->{static::$relations[$name][3]},
			'type' => static::$relations[$name][0]
		)));
	}

	/**
	 * Association de modèles
	 * @param Orm_association $association
	 * @return \Orm_model
	 */
	public function association(Orm_association $association) {
		switch ($association->type) {
			case Orm_association::TYPE_HAS_ONE:
				parent::$CI->db->where($association->field, $association->value)->limit(1);
				break;
			case Orm_association::TYPE_HAS_MANY:
				parent::$CI->db->where($association->field, $association->value);
				break;
			case Orm_association::TYPE_BELONGS_TO:
				parent::$CI->db->where($association->field, $association->value)->limit(1);
				break;
		}
		
		return $this;
	}

}

/* End of file Orm_model.php */
/* Location: ./application/libraries/Orm_model.php */
