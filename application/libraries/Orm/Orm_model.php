<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 3.2.10 (20141119)
 */
class Orm_model extends Orm {
    
    /**
     * Les valeur du modèle
     * @var array 
     */
    protected $_data = array();
    
    /**
     * Les champs a mettre à jour
     * @var array 
     */
    protected $_update = array();
    
    /**
     * L'espace de nom
     * @var NULL|string 
     */
    protected $_namespace = NULL;
    
    /**
     * Resource db
     * @var NULL|string 
     */
    protected $_db = NULL;
    
    /**
     * Configuration du model
     * @var boolean 
     */
    protected $_config = array();
    
    /**
     * Statut du cache
     * @var boolean 
     */
    protected $_cache = FALSE;
    
    /**
     * Durée du cache
     * @var interger 
     */
    protected $_tts = 0;

    /**
     * Constructeur
     * @param NULL|int|Orm_association $data
     */
    function __construct($data = NULL) {
        // Configuration par défaut
        $this->_config();
        
        // Connection à la base de donnée
        $this->_connect();

        // Génère les variables de l'objet
        $this->_generate();

        // Si la variable $data est un entier, c'est une clé primaire
        if (is_numeric($data)) {
            $this->_primary_key_find(new Orm_primary_key(static::$primary_key, $data));

        // Si la variable $data est une instance de la classe Orm_association
        } else if ($data instanceof Orm_association) {
            $this->_association_find($data);
        
        // Si la variable $data est un tableau 
        } else if (is_array($data)) {
            $this->set($data);
        }
    }
    
    /**
     * Configuration par défaut
     */
    protected function _config() {
        // Configuration du cache par défaut
        $this->_cache = parent::$config['cache'];
        $this->_tts = parent::$config['tts'];
        
        // Configuration de l'espace de nom
        $namespace = explode('\\', get_class($this));
        $this->_namespace = $namespace[0];
        
        // Génère la configuration des champs
        if (isset(static::$fields)) {        
            foreach (static::$fields as $field)
                $this->_config['fields'][$field['name']] = $field;
        }
        
        // Génère la configuration des associations
        if (isset(static::$associations)) {
            foreach (static::$associations as $field)
                $this->_config['associations'][$field['association_key']] = $field;
        }
        
        // Génère la configuration des validations
        if (isset(static::$validations)) {
            foreach (static::$validations as $field)
                $this->_config['validations'][$field['field']] = $field;
        }
    }
        
    /**
     * Connection à la base de donnée
     */
    protected function _connect() {
        $db = "db_$this->_namespace";
                
        // Si il exite une connxion		
        if ( ! isset(parent::$CI->{$db})) {
            // Nouvelle connexion
            parent::$CI->{$db} = parent::$CI->load->database($this->_namespace, TRUE);
                        
            // Si le cryptage est actif charge les éléments indispensable au cryptage
            if (parent::$config['encryption_enable']) {
                parent::$CI->{$db}->query("SET @@session.block_encryption_mode = 'aes-256-cbc';");
            }
        }
        
        // Création de la resource
        $this->_db =& parent::$CI->{$db};
    }
    
    /**
     * Gestion manuel du cache
     * @param booblean $status
     * @param integer $tts
     */
    public function use_result_cache($status = TRUE, $tts = 3600) {
        $this->_cache = $status;
        $this->_tts = $tts;
    }

    /**
     * Génère les variables dans le modèle
     * @return
     */
    protected function _generate() {
        foreach (static::$fields as $field)
            $this->_data[$field['name']] = NULL;
    }
    
    /**
     * Génère le select d'une requete SQL
     */
    private function _select() {
        $output = array();

        foreach (static::$fields as $config) {

            $orm_field = new Orm_field($config);

            if (parent::$config['encryption_enable'] && $orm_field->encrypt) {
                $output = array(
                    'field' => "CONVERT(AES_DECRYPT(FROM_BASE64(`$orm_field->name`), UNHEX('".parent::$config['encryption_key']."'), UNHEX(`vector`)) USING 'utf8') AS `$orm_field->name`",
                    'quote' => FALSE
                );
            } else if (parent::$config['binary_enable'] && $orm_field->binary) {
                $output = array(
                    'field' => "TO_BASE64(`$orm_field->name`) AS `$orm_field->name`",
                    'quote' => FALSE
                );
            } else {
                $output = array(
                    'field' => $orm_field->name,
                    'quote' => NULL
                );
            }
            
            $this->_db->select($output['field'], $output['quote']);
        }
    }
    
    /**
     * Génère l'insert et l'update d'une requete SQL
     * @param array $data
     */
    private function _set(array $data, $insert = TRUE) {
        // Initialise le champ
        $input = array();
        
        // Initilise la valeur du vecteur
        $vector_value = NULL;
        
        // Si le cryptage est activé, on renseigne le vecteur
        if (parent::$config['encryption_enable'])
            $vector_value = ( ! empty($data['vector'])) ? $data['vector'] : random_string('unique');

        // on boucle sur tous les champs de la table
        foreach ($data as $name => $value) {
            // Si la configuration n'existe pas
            if (($config = $this->_get_config_field($name)) === FALSE)
                continue;

            // Initialise l'objet champ
            $orm_field = new Orm_field($config, $value);
            
            // Si c'est un champ qu'on doit crypter
            if (parent::$config['encryption_enable'] && $orm_field->encrypt) {
                $input = array(
                    'field' => $orm_field->name,
                    'value' => "TO_BASE64(AES_ENCRYPT('{$this->_db->escape_str($orm_field->value)}', UNHEX('".parent::$config['encryption_key']."'), UNHEX('$vector_value')))",
                    'quote' => FALSE
                );

            // Si c'est un champ vecteur
            } else if (parent::$config['encryption_enable'] && $orm_field->name == 'vector') {
                
                // Si le champ vecteur n'est pas dans l'insert on l'ajoute
                if ($insert && ! in_array($orm_field->name, $this->_update))
                    $this->_update[] = $orm_field->name;
                
                $input = array(
                    'field' => $orm_field->name,
                    'value' => $vector_value,
                    'quote' => TRUE
                );

            // Si c'est un champ binaire
            } else if (parent::$config['binary_enable'] && $orm_field->binary) {
                $input = array(
                    'field' => $orm_field->name,
                    'value' => "FROM_BASE64('{$this->_db->escape_str($orm_field->value)}')",
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
            
            // Si le champ doit être mis a jour
            if (in_array($name, $this->_update))
                $this->_db->set($input['field'], $input['value'], $input['quote']);
        }
        
        // Si il y a des champs a modifier
        $has_values = ! empty($this->_update);

        // Réinitialise les champs a mettre à jour
        $this->_update = array();
        
        // Indique qu'il y a des champs a mettre a jour
        return $has_values;
    }

    /**
     * Teste si un champ est définie
     * @param type $name
     * @return boolean
     */
    public function __isset($name) {
        return isset($this->_data[$name]);
    }

    /**
     * Récupère la valeur d'un champ
     * @param type $name
     * @return integer|string|float
     */
    public function __get($name) {
        if ( ! isset($this->_data[$name]))
            return FALSE;

        return $this->_data[$name];
    }

    /**
     * Modifie la valeurd'un champ
     * @param string $name
     * @param integer|string|float $value
     */
    public function __set($name, $value) {
        // Si le champ existe
        if (isset($this->_data[$name])) {
            // Cast la valeur
            $this->_convert($name, $value);

            // Ajoute le champ a mettre à jour
            $this->_update[] = $name;
        }
    }

    /**
     * Passage d'objets en appelant la methode du nom de la relation
     * On peut passer en parametre des arguments pour filtrer le retour d'object
     * @param string $name
     * @param mixe $argument
     * @return Orm_model
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
     * Retoune les valeurs du modèle
     * @param mixe $name
     * @return mixe
     */
    public function get($name = NULL) {
        if ( ! empty($name))
            return isset($this->_data[$name]) ? $this->_data[$name] : FALSE;
        
        return $this->_data;
    }
    
    /**
     * Modifie les valeurs du modèle
     * @param array $data
     */
    public function set(array $data) {
        foreach ($data as $name => $value) {
            // Si le champs existe
            if (isset($this->_data[$name])) {
                // Met a jour le champ
                $this->{$name} = $value;
            }
        }
    }

    /**
     * Cast la valeur d'un champ
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
     * Cast un ensemble de valeur appartenant a plusieurs object
     * @param array $results
     * @return array
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
    
    /**
     * Retoune le nom de la resource
     * @return string
     */
    protected function get_db() {
        return $this->_db;
    }
    
    /**
     * Retoune l'espace de nom
     * @return string
     */
    public function get_namespace() {
        return $this->_namespace;
    }
    
    /**
     * Retourne les champs d'un modèle
     * @return array
     */
    public function get_fields() {
        $fields = array();
        
        foreach (static::$fields as $field) {
            $fields[] = $field['name'];
        }
        
        return $fields;
    }
    
    /**
     * Génère un WHERE en SQL
     * @param mixe $key
     * @param NULL|string|int|float $value
     * @param boolean $escape
     * @return Orm_model
     */
    public function where($key, $value = NULL, $escape = TRUE) {
        $this->_db->where($key, $value, $escape);

        return $this;
    }
    
    /**
     * Génère un WHERE % OR en SQL
     * @param mixe $key
     * @param NULL|string|int|float $value
     * @param boolean $escape
     * @return Orm_model
     */
    public function or_where($key, $value = NULL, $escape = TRUE) {
        $this->_db->or_where($key, $value, $escape);

        return $this;
    }

    /**
     * Génère un WHERE IN en SQL
     * @param mixe $key
     * @param NULL|string|int|float $value
     * @param boolean $escape
     * @return Orm_model
     */
    public function where_in($key = NULL, $values = NULL) {
        $this->_db->where_in($key, $values);

        return $this;
    }
    
    /**
     * Génère un WHERE NOT IN en SQL
     * @param mixe $key
     * @param NULL|string|int|float $value
     * @param boolean $escape
     * @return Orm_model
     */
    public function where_not_in($key = NULL, $values = NULL) {
        $this->_db->where_not_in($key, $values);

        return $this;
    }
    
    /**
     * Génère un WHERE OR % NOT IN en SQL
     * @param mixe $key
     * @param NULL|string|int|float $value
     * @param boolean $escape
     * @return Orm_model
     */
    public function or_where_not_in($key = NULL, $values = NULL) {
        $this->_db->or_where_not_in($key, $values);

        return $this;
    }

    /**
     * Génère un LIKE en SQL
     * @param mixe $field
     * @param string $match
     * @param string $side
     * @return Orm_model
     */
    public function like($field, $match = '', $side = 'both') {
        $this->_db->like($field, $match, $side);

        return $this;
    }
    
    /**
     * Génère un OR % LIKE en SQL
     * @param mixe $field
     * @param string $match
     * @param string $side
     * @return Orm_model
     */
    public function or_like($field, $match = '', $side = 'both') {
        $this->_db->or_like($field, $match, $side);

        return $this;
    }
    
    /**
     * Génère un NOT LIKE en SQL
     * @param mixe $field
     * @param string $match
     * @param string $side
     * @return Orm_model
     */
    public function not_like($field, $match = '', $side = 'both') {
        $this->_db->or_like($field, $match, $side);

        return $this;
    }
    
    /**
     * Génère un OR % NOT LIKE en SQL
     * @param mixe $field
     * @param string $match
     * @param string $side
     * @return Orm_model
     */
    public function or_not_like($field, $match = '', $side = 'both') {
        $this->_db->or_not_like($field, $match, $side);

        return $this;
    }

    /**
     * Génère un GROUP BY en SQL
     * @param string $by
     * @return Orm_model
     */
    public function group_by($by) {
        $this->_db->group_by($by);

        return $this;
    }

    /**
     * Génère un HAVING en SQL
     * @param string $key
     * @param string $value
     * @param boolean $escape
     * @return Orm_model
     */
    public function having($key, $value = '', $escape = TRUE) {
        $this->_db->having($key, $value, $escape);

        return $this;
    }

    /**
     * Génère un ORDER BY en SQL
     * @param string $orderby
     * @param string $direction
     * @return Orm_model
     */
    public function order_by($orderby, $direction = '') {
        $this->_db->order_by($orderby, $direction);

        return $this;
    }

    /**
     * Génère un LIMIT en SQL
     * @param string $value
     * @param string $offset
     * @return Orm_model
     */
    public function limit($value, $offset = '') {
        $this->_db->limit($value, $offset);

        return $this;
    }

    /**
     * Génère un COUNT en SQL
     * @return int
     */
    public function count() {
        return (int) $this->_db->count_all_results(static::$table);
    }

    /**
     * Génère la requête
     * @return array
     */
    protected function _result() {
        // Initialisation de l'objet table
        $orm_table = new Orm_table(static::$table);

        // Prépare le select
        $this->_select();

        // Prépare le from
        $this->_db->from($orm_table->name);

        // Si le cache est activé
        if ($this->_cache) {
            $table = "orm_$orm_table->name";
            $hash = md5($this->_db->_compile_select());
            $key = "{$table}_{$hash}";
            
           // Si l'arbre des clés existe
            if ( ! $keys = parent::$CI->cache->get($table) OR ! isset($keys[$key])) {
                // Récupère les clés existantes
                $keys = (is_array($keys)) ? $keys : array();

                // Enregistre la clé
                $keys[$key] = $key;

                // Sauvegarde
                parent::$CI->cache->save($table, $keys, parent::$config['tts']);
            }
            
            // Si la clé existe
            if ( ! $result = parent::$CI->cache->get($key)) {
                // Exécute la requête
                $result = $this->_db->get()->result_array();

                // Sauvegarde les résultats
                parent::$CI->cache->save($key, $result, parent::$config['tts']);
            }

            // Vide la requete
            $this->_db->_reset_select();

            // Retoune les résultats en cache
            return $result;
        }

        // Retourne les résultats sans cache
        return $this->_db->get()->result_array();
    }

    /**
     * Recherche plusieurs modèles
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
     * Recherche un modèle
     * @return null|objet
     */
    public function find_one() {
        // Limite la requête a un objet
        $this->_db->limit(1);

        // Exécute la requête
        $objects = $this->find();

        // Retoune le premier résultat
        return (isset($objects[0])) ? $objects[0] : NULL;
    }

    /**
     * Sauvegarde un modèle en base de donnée
     * @param boolean $force_insert
     * @return boolean
     */
    public function save($force_insert = FALSE) {
        // Initialisation de l'objet table
        $orm_table = new Orm_table(static::$table);

        // Initialisation de l'objet clé primaire
        $orm_primary_key = new Orm_primary_key(static::$primary_key, $this->{static::$primary_key});

        // Si le cache est activé
        if ($this->_cache) {
            // Nom de l'arbre de clés
            $table = "orm_$orm_table->name";
            
            // Si l'arbre de clés existe
            if ($keys = parent::$CI->cache->get($table)) {
                if (is_array($keys)) {
                    // Parcours les clés pour les supprimer
                    foreach ($keys as $key) {
                        parent::$CI->cache->delete($key);
                    }
                }
                
                // Supprime l'arbre de clés
                parent::$CI->cache->delete($table);
            }
        }
        
        // Type de requête
        $has_insert = (empty($orm_primary_key->value) || $force_insert === TRUE) ? TRUE : FALSE;

        // Mise a jour des champs, et retourne si il y a des champs a modifier
        $has_values = $this->_set($this->_data, $has_insert);
        
        // Si la requete est de type insert
        if ($has_insert) {
            // Exécute la requête
            $query = $this->_db->from($orm_table->name)->insert();
            
            // Si l'insertion est correcte
            if ($query === TRUE) {
                // Met a jour la clé primaire
                $this->{$orm_primary_key->name} = $this->_db->insert_id();
            }
            
            // Retourne le résultat de la requête
            return $query;
            
         // Si la requete est de type update
        } else {
            // Il y a aucun champ a mettre a jour
            if ($has_values === FALSE)
                return FALSE;
            
            // Exécute la requête
            return $this->_db->from($orm_table->name)->where($orm_primary_key->name, $orm_primary_key->value)->update();
        }
        
        // La requête a échoué
        return FALSE;
    }

    /**
     * Efface un modèle en base de donnée
     * @return boolean
     */
    public function remove() {
        // Initialisation de l'objet table
        $orm_table = new Orm_table(static::$table);

        // Initialisation de l'objet clé primaire
        $orm_primary_key = new Orm_primary_key(static::$primary_key, $this->{static::$primary_key});

        // Si le cache est activé
        if ($this->_cache) {
            // Nom de l'arbre de clés
            $table = "orm_$orm_table->name";
            
            // Si l'arbre de clés existe
            if ($keys = parent::$CI->cache->get($table)) {
                if (is_array($keys)) {
                    // Parcours les clés pour les supprimer
                    foreach ($keys as $key) {
                        parent::$CI->cache->delete($key);
                    }
                }
                
                // Supprime l'arbre de clés
                parent::$CI->cache->delete($table);
            }
        }

        // Exécute la requête
        return $this->_db->where($orm_primary_key->name, $orm_primary_key->value)->delete($orm_table->name);
    }
        
    /**
     * Recherche la configuration d'un champ
     * @param string $name
     * @return boolean|array
     */
    protected function _get_config_field($name) {
        try {
            // Si le champ existe
            if (isset($this->_config['fields'][$name]))
                return $this->_config['fields'][$name];
            
            $error = (parent::$CI->input->is_cli_request())
                ? "Le champ $name est introuvable dans le modèle ".get_class($this).PHP_EOL
                : "Le champ <b>$name</b> est introuvable dans le modèle <b>".get_class($this)."</b></br/>";

            throw new Exception($error);

            // Aucune configuration n'a été trouvé
            return FALSE;
        } catch (Exception $e) {
            exit("L'ORM a rencontré un problème : {$e->getMessage()}");
        }
    }
    
    /**
     * Recherche la configuration d'un champ association
     * @param string $association_key
     * @return boolean|array
     */
    protected function _get_config_association($association_key) {
        try {
            // Si le champ existe
            if (isset($this->_config['associations'][$association_key]))
                return $this->_config['associations'][$association_key];
            
            $error = (parent::$CI->input->is_cli_request())
                ? "L'association $association_key est introuvable dans le modèle ".get_class($this).PHP_EOL
                : "L'association <b>$association_key</b> est introuvable dans le modèle <b>".get_class($this)."</b></br/>";

            throw new Exception($error);

            // Aucune configuration n'a été trouvé
            return FALSE;
        } catch (Exception $e) {
            exit("L'ORM a rencontré un problème : {$e->getMessage()}");
        }
    }
    
    /**
     * Recherche la configuration d'un champ validation
     * @param string $field
     * @return boolean|array
     */
    protected function _get_config_validation($field) {
        try {
            // Si le champ existe
            if (isset($this->_config['validations'][$field]))
                return $this->_config['validations'][$field];
            
            $error = (parent::$CI->input->is_cli_request())
                ? "La validation du champ $field est introuvable dans le modèle ".get_class($this).PHP_EOL
                : "La validation du champ <b>$field</b> est introuvable dans le modèle <b>".get_class($this)."</b></br/>";

            throw new Exception($error);

            // Aucune configuration n'a été trouvé
            return FALSE;
        } catch (Exception $e) {
            exit("L'ORM a rencontré un problème : {$e->getMessage()}");
        }
    }

    /**
     * Retourne un object à l'aide de ça clé primaire
     * @param Orm_primary_key $primary_key
     * @return Orm_model
     */
    protected function _primary_key_find(Orm_primary_key $primary_key) {

        $object = $this->where($primary_key->name, $primary_key->value)->find_one();
        
        // Si l'object existe
        if ( ! empty($object))
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
                $this->_db->where($association->primary_key, $association->value)->limit(1);
                break;
            case Orm_association::TYPE_HAS_MANY:
                $this->_db->where($association->foreign_key, $association->value);
                break;
            case Orm_association::TYPE_BELONGS_TO:
                $this->_db->where($association->primary_key, $association->value)->limit(1);
                break;
        }

        return $this;
    }

    /**
     * Exécute la vérification d'un modèle et retourne un tableau d'erreurs
     * @return array|boolean
     */
    public function validate() {
        $errors = array();

        if (empty(static::$validations))
            return $errors;

        foreach (static::$validations as $validation) {  
            if (($config = $this->_get_config_field($validation['field'])) === FALSE)
                return FALSE;

            $orm_validation = new Orm_validation($validation);
            $orm_field = new Orm_field($config, $this->_data[$validation['field']]);

            if ( ! $orm_validation->validate($orm_field)) {
                // Définie le message d'erreur
                $errors[$orm_validation->field] = sprintf($orm_validation->message, $orm_validation->field, $orm_field->value);
            }
        }

        return $errors;
    }

    /**
     * Vérifie si les valeurs du modèle sont correctes
     * @return boolean
     */
    public function is_valid() {
        // Validation
        $errors = $this->validate();

        return empty($errors);
    }

}

/* End of file Orm_model.php */
/* Location: ./application/libraries/Orm_model.php */
