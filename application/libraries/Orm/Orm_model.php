<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 3.0 (20140611)
 */
class Orm_model extends Orm {

    /**
     * Constructeur
     * @param NULL|int|array $data
     * @return object|
     */
    function __construct($data = NULL) {

        // Connection à la base de donnée
        $this->_connect();

        // Créer les variables de l'objet
        $this->_generate();

        // Si la variable $data est un entier, c'est une clé primaire
        if (is_numeric($data)) {
            return $this->_find_primary_key(new Orm_primary_key(array(
                'name' => static::$primary_key,
                'value' => $data
            )));

            // Si la variable $data est une instance de la classe Orm_association
        } else if ($data instanceof Orm_association) {
            $this->_association($data);
        }
    }

    protected function _namespace() {
        $namespace = explode('\\', get_class($this));
        return $namespace[0];
    }

    /**
     * Créer les variables dans le modèle
     * @return
     */
    protected function _connect() {
        // Si il exite une connxion		
        if (!isset(parent::$CI->{'db_'.$this->_namespace()})) {
            // Nouvelle connexion
            parent::$CI->{'db_'.$this->_namespace()} = parent::$CI->load->database($this->_namespace(), TRUE);
        }
    }

    /**
     * Créer les variables dans le modèle
     * @return
     */
    protected function _generate() {
        foreach (static::$fields as $field)
            $this->{$field['name']} = '';
    }

    private function _select() {
        $output = array();

        foreach (static::$fields as $field) {
            
            $orm_field = new Orm_field($field);
            
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
            
            parent::$CI->{'db_'.$this->_namespace()}->select($output['field'], $output['quote']);
        }
    }

    private function _update(array $data) {
        $input = array();
        $vector_value = NULL;

        if (parent::$config['encryption_enable'])
            $vector_value = ( ! empty($data['vector'])) ? $data['vector'] : random_string('unique');

        // on boucle sur tous les champs de la table
        foreach ($data as $name => $value) {
            // Configuratino du champ
            $config = $this->_get_field($name);
            
            // Si la configuration n'existe pas
            if ($config === FALSE)
                return;
            
            // Initialise l'objet champ
            $orm_field = new Orm_field($config);
            
            // Renseigne la valeur du champ
            $orm_field->value = $value;

            // Si c'est un champ qu'on doit crypter
            if (parent::$config['encryption_enable'] && $orm_field->encrypt) {
                $input = array(
                    'field' => $orm_field->name,
                    'value' => "TO_BASE64(AES_ENCRYPT('".parent::$CI->{'db_'.$this->_namespace()}->escape_str($orm_field->value)."', UNHEX('".parent::$config['encryption_key']."'), UNHEX('".$vector_value."')))",
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
                    'value' => "FROM_BASE64('".parent::$CI->{'db_'.$this->_namespace()}->escape_str($orm_field->value)."')",
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
            
            parent::$CI->{'db_'.$this->_namespace()}->set($input['field'], $input['value'], $input['quote']);
        }
    }

    /**
     * Convertie la valeur d'une variable
     * @param mixe $name
     * @param mixe $value
     */
    public function __set($name, $value) {
        $this->_convert_type($name, $value);
    }

    /**
     * Convertie les valeurs des variables
     * @param NULL|string $data
     * @return Orm_model
     */
    private function _convert_types(array $data) {
        foreach ($data as $name => $value) {
            $this->_convert_type($name, $value);
        }

        return $this;
    }
    
    /**
     * Convertie la valeur d'une variable
     * @param mixe $name
     * @param mixe $value
     */
    public function _convert_type($name, $value) {
        // Configuration
        $config = $this->_get_field($name);
        
        // Si la configuration n'existe pas
        if ($config === FALSE)
            return;
        
        // Initialise l'objet
        $orm_field = new Orm_field($config);
        
        // Renseigne la valeur du champ
        $orm_field->value = $value;
        
        // Convertie la valeur du champ
        $this->{$orm_field->name} = $orm_field->convert();
    }

    /**
     * Where
     * @param mixe $key
     * @param NULL|string|int|float $value
     * @param boolean $escape
     * @return Orm_model
     */
    public function where($key, $value = NULL, $escape = TRUE) {
        parent::$CI->{'db_'.$this->_namespace()}->where($key, $value, $escape);

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
        parent::$CI->{'db_'.$this->_namespace()}->where_in($key, $values);

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
        parent::$CI->{'db_'.$this->_namespace()}->like($field, $match, $side);

        return $this;
    }

    /**
     * Group by
     * @param string $by
     * @return Orm_model
     */
    public function group_by($by) {
        parent::$CI->{'db_'.$this->_namespace()}->group_by($by);

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
        parent::$CI->{'db_'.$this->_namespace()}->having($key, $value, $escape);

        return $this;
    }

    /**
     * Order by
     * @param string $orderby
     * @param string $direction
     * @return Orm_model
     */
    public function order_by($orderby, $direction = '') {
        parent::$CI->{'db_'.$this->_namespace()}->order_by($orderby, $direction);

        return $this;
    }

    /**
     * Lmit
     * @param string $value
     * @param string $offset
     * @return Orm_model
     */
    public function limit($value, $offset = '') {
        parent::$CI->{'db_'.$this->_namespace()}->limit($value, $offset);

        return $this;
    }

    /**
     * Compte les résultats
     * @return int
     */
    public function count() {
        return (int) parent::$CI->{'db_'.$this->_namespace()}->count_all_results(static::$table);
    }

    /**
     * Recherche en base de donnée
     * @return array
     */
    protected function _data_find() {
        $this->_select();

        parent::$CI->{'db_'.$this->_namespace()}->from(static::$table);

        // Si le cache est activé
        if (parent::$config['cache']) {
            $cache_id = 'orm_'.static::$table;
            $cache_key = md5(parent::$CI->{'db_'.$this->_namespace()}->_compile_select());

            // Vérifie si le cache existe
            if (!$data = parent::$CI->cache->get($cache_id) OR ! isset($data[$cache_key])) {

                $data = (is_array($data)) ? $data : array();

                $data[$cache_key] = parent::$CI->{'db_'.$this->_namespace()}->get()->result_array();

                parent::$CI->cache->save($cache_id, $data, parent::$config['tts']);
            }

            // Vide la requete
            parent::$CI->{'db_'.$this->_namespace()}->_reset_select();

            // Retoune les résultats en cache
            return $data[$cache_key];
        }

        // Retourne les résultats
        return parent::$CI->{'db_'.$this->_namespace()}->get()->result_array();
    }

    /**
     * Cherche plusieurs objets
     * @return array
     */
    public function find() {
        $objects = array();
        
        // Répuère les objets
        $data = $this->_data_find();
        
        // Si aucun résultat trouvé
        if (empty($data))
            return array();
        
        // Convertie les champs des objets
        foreach ($data as $value)
            $objects[] = clone $this->_convert_types($value);
        
        // Retoune les objets
        return $objects;
    }

    /**
     * Cherche un objet
     * @return boolean|objet
     */
    public function find_one() {
        // Limite la requête a un objet
        parent::$CI->{'db_'.$this->_namespace()}->limit(1);
        
        // Exécute la requête
        $data = $this->find();

        // Retoune le premier résultat
        return (isset($data[0])) ? $data[0] : array();
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
        $orm_primary_key = new Orm_primary_key(array(
            'name' => static::$primary_key,
            'value' => $this->{static::$primary_key}
        ));
        
        // Suppression du cache
        if (parent::$config['cache'])
            parent::$CI->cache->delete('orm_'.$orm_table->name);
        
        // Définition de la table
        parent::$CI->{'db_'.$this->_namespace()}->from($orm_table->name);

        // Prépare les champs a mette a jour
        $this->_update(get_object_vars($this));
        
        // Si c'est une insertion
        if ( ! empty($orm_primary_key->value) && $force_insert === FALSE) {
            // Exécute la requête
            return parent::$CI->{'db_'.$this->_namespace()}->where($orm_primary_key->name, $orm_primary_key->value)->update();
            
        // Si c'est un update
        } else {
            // Exécute la requête
            parent::$CI->{'db_'.$this->_namespace()}->insert();
            
            // Retourne l'id
            return $this->{$orm_primary_key->name} = parent::$CI->{'db_'.$this->_namespace()}->insert_id();
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
        $orm_primary_key = new Orm_primary_key(array(
            'name' => static::$primary_key,
            'value' => $this->{static::$primary_key}
        ));
        
        // Supprime le cache
        if (parent::$config['cache'])
            parent::$CI->cache->delete('orm_'.static::$table);
        
        // Exécute la requête
        return parent::$CI->{'db_'.$this->_namespace()}->where($orm_primary_key->name, $orm_primary_key->value)->delete($orm_table->name);
    }

    /**
     * Passage d'objets en appelant la methode du nom de la relation
     * On peut passer en parametre des arguments pour filtrer le retour d'object
     * @param type $get
     * @return boolean|Orm_model
     */
    public function __call($name, $argument) {
        // Configuration
        $config = $this->_get_association($name);
        
        // Si la configuration n'existe pas
        if ($config === FALSE)
            return FALSE;
        
        // Initialisation de l'objet association
        $orm_association = new Orm_association($config);
        
        // Associe le modèle en cours
        $orm_association->associated_model($this);
        
        // Retoune le nouveau modèle associé
        return $orm_association->create_model();
    }
    
    protected function _get_field($name) {
        if (empty(static::$fields))
            return FALSE;
        
        foreach (static::$fields as $field) {
            if ($field['name'] == $name)
                return $field;
        }
        
        return FALSE;
    }
    
    protected function _get_association($association_key) {
        if (empty(static::$associations))
            return FALSE;
        
        foreach (static::$associations as $association) {
            if ($association['association_key'] == $association_key)
                return $association;
        }
        
        return FALSE;
    }
    
    protected function _get_validation($field) {
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
    protected function _find_primary_key(Orm_primary_key $primary_key) {
        return $this->where($primary_key->name, $primary_key->value)->find_one();
    }

    /**
     * Association de modèles
     * @param Orm_association $association
     * @return Orm_model
     */
    protected function _association(Orm_association $association) {
        switch ($association->type) {
            case Orm_association::TYPE_HAS_ONE:
                parent::$CI->{'db_'.$this->_namespace()}->where($association->primary_key, $association->value)->limit(1);
                break;
            case Orm_association::TYPE_HAS_MANY:
                parent::$CI->{'db_'.$this->_namespace()}->where($association->primary_key, $association->value);
                break;
            case Orm_association::TYPE_BELONGS_TO:
                parent::$CI->{'db_'.$this->_namespace()}->where($association->primary_key, $association->value)->limit(1);
                break;
        }

        return $this;
    }

}

/* End of file Orm_model.php */
/* Location: ./application/libraries/Orm_model.php */
