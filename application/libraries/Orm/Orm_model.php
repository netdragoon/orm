<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 3.0 (20140611)
 */
class Orm_model extends Orm {
    
    protected $_data = array();
    
    protected $_update = array();
    
    protected $_namespace = NULL;

    /**
     * Constructeur
     * @param NULL|int|array $data
     * @return object|
     */
    function __construct($data = NULL) {
        
        $this->_namespace();

        // Connection à la base de donnée
        $this->_connect();

        // Créer les variables de l'objet
        $this->_generate();

        // Si la variable $data est un entier, c'est une clé primaire
        if (is_numeric($data)) {
            $this->_primary_key_find(new Orm_primary_key(static::$primary_key, $data));

        // Si la variable $data est une instance de la classe Orm_association
        } else if ($data instanceof Orm_association) {
            $this->_association_find($data);
        }
    }

    protected function _namespace() {
        $namespace = explode('\\', get_class($this));
        $this->_namespace = $namespace[0];
    }
    
    protected function _db() {
        return 'db_'.$this->_namespace;
    }

    /**
     * Créer les variables dans le modèle
     * @return
     */
    protected function _connect() {  
        // Si il exite une connxion		
        if (!isset(parent::$CI->{$this->_db()})) {
            // Nouvelle connexion
            parent::$CI->{$this->_db()} = parent::$CI->load->database($this->_namespace, TRUE);
        }
    }

    /**
     * Créer les variables dans le modèle
     * @return
     */
    protected function _generate() {       
        foreach (static::$fields as $field)
            $this->_data[$field['name']] = NULL;
    }

    private function _select() {
        $output = array();

        foreach (static::$fields as $config) {
            
            $orm_field = new Orm_field($config);
            
            if (parent::$config['encryption_enable'] && $orm_field->encrypt) {
                $output = array(
                    'field' => "CONVERT(AES_DECRYPT(FROM_BASE64(`".$orm_field->name."`), UNHEX('".parent::$config['encryption_key']."'), UNHEX(`vector`)) USING 'utf8') AS `".$orm_field->name."`",
                    'quote' => FALSE
                );
            } else if (parent::$config['binary_enable'] && $orm_field->binary) {
                $output = array(
                    'field' => "TO_BASE64(`".$orm_field->name."`) AS `".$orm_field->name."`",
                    'quote' => FALSE
                );
            } else {
                $output = array(
                    'field' => $orm_field->name,
                    'quote' => NULL
                );
            }
            
            parent::$CI->{$this->_db()}->select($output['field'], $output['quote']);
        }
    }

    private function _update(array $data) {
        $input = array();
        $vector_value = NULL;

        if (parent::$config['encryption_enable'])
            $vector_value = ( ! empty($data['vector'])) ? $data['vector'] : random_string('unique');

        // on boucle sur tous les champs de la table
        foreach ($data as $name => $value) {
            
            // Si le champs n'est pas dans la liste on ne le met pas à jour
            if ( ! in_array($name, $this->_update))
                continue;
            
            // Si la configuration n'existe pas
            if (($config = $this->_get_config_field($name)) === FALSE)
                return;
            
            // Initialise l'objet champ
            $orm_field = new Orm_field($config, $value);

            // Si c'est un champ qu'on doit crypter
            if (parent::$config['encryption_enable'] && $orm_field->encrypt) {
                $input = array(
                    'field' => $orm_field->name,
                    'value' => "TO_BASE64(AES_ENCRYPT('".parent::$CI->{$this->_db()}->escape_str($orm_field->value)."', UNHEX('".parent::$config['encryption_key']."'), UNHEX('".$vector_value."')))",
                    'quote' => FALSE
                );

            // Si c'est un champ vecteur
            } else if (parent::$config['encryption_enable'] && $orm_field->name == 'vector') {                
                $input = array(
                    'field' => $orm_field->name,
                    'value' => $vector_value,
                    'quote' => TRUE
                );

            // Si c'est un champ binaire
            } else if (parent::$config['binary_enable'] && $orm_field->binary) {                
                $input = array(
                    'field' => $orm_field->name,
                    'value' => "FROM_BASE64('".parent::$CI->{$this->_db()}->escape_str($orm_field->value)."')",
                    'quote' => FALSE
                );

            // Par défaut
            } else {                
                $input = array(
                    'field' => $orm_field->name,
                    'value' => $orm_field->value,
                    'quote' => TRUE
                );
            }
            
            parent::$CI->{$this->_db()}->set($input['field'], $input['value'], $input['quote']);
        }
        
        // Réinitialise les champs a mettre à jour
        $this->_update = array();
    }
    
    /**
     * 
     * @param type $name
     * @return type
     */
    public function __isset($name) {
        return isset($this->_data[$name]);
    }
    
    /**
     * 
     * @param type $name
     */
    public function __unset($name) {
    }
    
    /**
     * 
     * @param type $name
     * @return boolean
     */
    public function __get($name) {
        if ( ! array_key_exists($name, $this->_data))
            return FALSE;
        
        return $this->_data[$name];
    }

    /**
     * Convertie la valeur d'une variable
     * @param mixe $name
     * @param mixe $value
     */
    public function __set($name, $value) {
        // Si le champ existe
        if (array_key_exists($name, $this->_data)) {
            // Cast la valeur
            $this->_convert($name, $value);
            
            // Ajoute le champ a mettre à jour
            $this->_update[] = $name;
        }
    }
    
    /**
     * Passage d'objets en appelant la methode du nom de la relation
     * On peut passer en parametre des arguments pour filtrer le retour d'object
     * @param type $get
     * @return boolean|Orm_model
     */
    public function __call($name, $argument) {
        // Si la configuration n'existe pas
        if (($config = $this->_get_config_association($name)) === FALSE)
            return $config;
        
        
        
        // Initialisation de l'objet association
        $orm_association = new Orm_association($config, $this);

        // Retoune le nouveau modèle associé
        return $orm_association->associated();
    }
    
    /**
     * Convertie la valeur d'une variable
     * @param mixe $name
     * @param mixe $value
     */
    public function _convert($name, $value) {
        // Si la configuration n'existe pas
        if (($config = $this->_get_config_field($name)) === FALSE)
            return $config;
                        
        // Initialise l'objet
        $orm_field = new Orm_field($config, $value);
                                        
        // Convertie la valeur du champ
        $this->_data[$orm_field->name] = $orm_field->convert();
    }
    
    /**
     * 
     * @param array $results
     * @return \Orm_model
     */
    private function _convert_all(array $results) {
        $objects = array();
        
        foreach ($results as $result) {
            // Clone l'object en cours
            $object = clone $this;

            foreach ($result as $name => $value)
                $object->_convert($name, $value);
            
            $objects[] = $object;
        }
        
        return $objects;
    }
    
    public function get_namespace() {
        return $this->_namespace;
    }

    /**
     * Where
     * @param mixe $key
     * @param NULL|string|int|float $value
     * @param boolean $escape
     * @return Orm_model
     */
    public function where($key, $value = NULL, $escape = TRUE) {
        parent::$CI->{$this->_db()}->where($key, $value, $escape);

        return $this;
    }

    /**
     * Where
     * @param mixe $key
     * @param NULL|string|int|float $value
     * @param boolean $escape
     * @return Orm_model
     */
    public function where_in($key = NULL, $values = NULL) {
        parent::$CI->{$this->_db()}->where_in($key, $values);

        return $this;
    }

    /**
     * Like
     * @param mixe $field
     * @param string $match
     * @param string $side
     * @return Orm_model
     */
    public function like($field, $match = '', $side = 'both') {
        parent::$CI->{$this->_db()}->like($field, $match, $side);

        return $this;
    }

    /**
     * Group by
     * @param string $by
     * @return Orm_model
     */
    public function group_by($by) {
        parent::$CI->{$this->_db()}->group_by($by);

        return $this;
    }

    /**
     * Having
     * @param string $key
     * @param string $value
     * @param boolean $escape
     * @return Orm_model
     */
    public function having($key, $value = '', $escape = TRUE) {
        parent::$CI->{$this->_db()}->having($key, $value, $escape);

        return $this;
    }

    /**
     * Order by
     * @param string $orderby
     * @param string $direction
     * @return Orm_model
     */
    public function order_by($orderby, $direction = '') {
        parent::$CI->{$this->_db()}->order_by($orderby, $direction);

        return $this;
    }

    /**
     * Lmit
     * @param string $value
     * @param string $offset
     * @return Orm_model
     */
    public function limit($value, $offset = '') {
        parent::$CI->{$this->_db()}->limit($value, $offset);

        return $this;
    }

    /**
     * Compte les résultats
     * @return int
     */
    public function count() {
        return (int) parent::$CI->{$this->_db()}->count_all_results(static::$table);
    }

    /**
     * Recherche en base de donnée
     * @return array
     */
    protected function _result() {        
        // Initialisation de l'objet table
        $orm_table = new Orm_table(static::$table);
        
        // Prépare le select
        $this->_select();
        
        // Prépare le from
        parent::$CI->{$this->_db()}->from($orm_table->name);

        // Si le cache est activé
        if (parent::$config['cache']) {
            $cache_id = 'orm_'.$orm_table->name;
            $cache_key = md5(parent::$CI->{$this->_db()}->_compile_select());

            // Vérifie si le cache existe
            if (!$data = parent::$CI->cache->get($cache_id) OR ! isset($data[$cache_key])) {
                
                // récupère le cache existant
                $data = (is_array($data)) ? $data : array();
                
                // Exécute la requête
                $data[$cache_key] = parent::$CI->{$this->_db()}->get()->result_array();

                // Sauvegarde les résultats en cache
                parent::$CI->cache->save($cache_id, $data, parent::$config['tts']);
            }

            // Vide la requete
            parent::$CI->{$this->_db()}->_reset_select();

            // Retoune les résultats en cache
            return $data[$cache_key];
        }
        
        // Retourne les résultats sans cache
        return parent::$CI->{$this->_db()}->get()->result_array();
    }
    
    /**
     * Cherche plusieurs objets
     * @return array
     */
    public function find() {
        // Répuère les objets        
        $objects = $this->_convert_all($this->_result());
        
        // Si aucun résultat trouvé
        if (empty($objects))
            return array();
        
        // Retoune les objets
        return $objects;
    }

    /**
     * Cherche un objet
     * @return null|objet
     */
    public function find_one() {
        // Limite la requête a un objet
        parent::$CI->{$this->_db()}->limit(1);
        
        // Exécute la requête
        $objects = $this->find();
        
        // Retoune le premier résultat
        return (isset($objects[0])) ? $objects[0] : NULL;
    }

    /**
     * Sauvegarde un objet
     * @param boolean $force_insert
     * @return boolean
     */
    public function save($force_insert = FALSE) {
        // Initialisation de l'objet table
        $orm_table = new Orm_table(static::$table);
        
        // Initialisation de l'objet clé primaire
        $orm_primary_key = new Orm_primary_key(static::$primary_key, $this->{static::$primary_key});
        
        // Suppression du cache
        if (parent::$config['cache'])
            parent::$CI->cache->delete('orm_'.$orm_table->name);
        
        // Définition de la table
        parent::$CI->{$this->_db()}->from($orm_table->name);

        // Prépare les champs a mette a jour
        $this->_update($this->_data);
                
        // Si c'est une insertion
        if ( ! empty($orm_primary_key->value) && $force_insert === FALSE) {
            // Exécute la requête
            return parent::$CI->{$this->_db()}->where($orm_primary_key->name, $orm_primary_key->value)->update();
            
        // Si c'est un update
        } else {
            // Exécute la requête
            parent::$CI->{$this->_db()}->insert();
            
            // Retourne l'id
            return $this->{$orm_primary_key->name} = parent::$CI->{$this->_db()}->insert_id();
        }
    }

    /**
     * Supprime un objet
     * @return boolean
     */
    public function remove() {
        // Initialisation de l'objet table
        $orm_table = new Orm_table(static::$table);
        
        // Initialisation de l'objet clé primaire
        $orm_primary_key = new Orm_primary_key(static::$primary_key,$this->{static::$primary_key});
        
        // Supprime le cache
        if (parent::$config['cache'])
            parent::$CI->cache->delete('orm_'.$orm_table);
        
        // Exécute la requête
        return parent::$CI->{$this->_db()}->where($orm_primary_key->name, $orm_primary_key->value)->delete($orm_table->name);
    }
    
    protected function _get_config_field($name) {        
        if (empty(static::$fields))
            return FALSE;
        
        foreach (static::$fields as $field) {
            if ($field['name'] === $name)
                return $field;
        }
        
        return FALSE;
    }
    
    protected function _get_config_association($association_key) {
        if (empty(static::$associations))
            return FALSE;
        
        foreach (static::$associations as $association) {
            if ($association['association_key'] == $association_key)
                return $association;
        }
        
        return FALSE;
    }
    
    protected function _get_config_validation($field) {
        if (empty(static::$validations))
            return FALSE;
        
        foreach (static::$validations as $validation) {
            if ($validation['field'] == $field)
                return $validation;
        }
        
        return FALSE;
    }

    /**
     * Retourne l'object à l'aide de ça clé primaire
     * @param Orm_primary_key $primary_key
     * @return Orm_model
     */
    protected function _primary_key_find(Orm_primary_key $primary_key) {
        
        $object = $this->where($primary_key->name, $primary_key->value)->find_one();
       
        $this->_data = $object->_data;
    }

    /**
     * Association de modèles
     * @param Orm_association $association
     * @return Orm_model
     */
    protected function _association_find(Orm_association $association) {
        switch ($association->type) {
            case Orm_association::TYPE_HAS_ONE:
                parent::$CI->{$this->_db()}->where($association->primary_key, $association->value)->limit(1);
                break;
            case Orm_association::TYPE_HAS_MANY:
                parent::$CI->{$this->_db()}->where($association->primary_key, $association->value);
                break;
            case Orm_association::TYPE_BELONGS_TO:
                parent::$CI->{$this->_db()}->where($association->primary_key, $association->value)->limit(1);
                break;
        }

        return $this;
    }
    
    /**
     * 
     * @return \Orm_field|array
     */
    public function validate() {
        $errors = array();
        
        if (empty(static::$validations))
            return $errors;
        
        foreach (static::$validations as $validation) {
            if (($config = $this->_get_config_field($validation['field'])) === FALSE)
                return;
            
            $orm_validation = new Orm_validation($validation);
            $orm_field = new Orm_field($config, $this->_data[$validation['field']]);
            
            if ( ! $orm_validation->validate($orm_field))
                $errors[] = $orm_field;
        }
        
        return $errors;
    }
    
    /**
     * 
     * @return boolean
     */
    public function is_validate() {
        // Validation
        $errors = $this->validate();
        
        return empty($errors);
    }

}

/* End of file Orm_model.php */
/* Location: ./application/libraries/Orm_model.php */
