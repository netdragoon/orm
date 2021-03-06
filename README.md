# ORM
Object Relational Mapping for Codeigniter 2 and Codeigniter 3

####The project is no longer maintained we recommend using Origami project https://github.com/maltyxx/origami.

## Installation
### Step 1 Installation by Composer
#### Edit /composer.json
```json
{
    "require": {
        "maltyxx/orm": "3.3.*"
    }
}
```
#### Run composer update
```shell
composer update
```

### Step 2 Create files
```txt
/application/controllers/Modelgenerator.php for CodeIgniter 3
/application/controllers/modelgenerator.php for CodeIgniter 2
```
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require(APPPATH.'/libraries/Orm/controllers/Modelgenerator.php');
```
```txt
/application/helpers/orm_helper.php
```
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require(APPPATH.'/libraries/Orm/helpers/orm_helper.php');
```
```txt
/application/language/english/orm_lang.php
```
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require(APPPATH.'/libraries/Orm/language/english/orm_lang.php');
```
```txt
/application/libraries/Orm.php
```
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require(APPPATH.'/libraries/Orm/Orm.php');
```

### Step 3 Configuration database
Configuration /application/config/database.php

```php
$db['databasename'] = array(
	'dsn'	=> '',
	'hostname' => 'localhost',
	'username' => '',
	'password' => '',
	'database' => '',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => TRUE,
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);

```

### Step 4 Model Generator
#### Method CLI
```bash
php index.php modelgenerator index
```
OR

#### Method WEB
http://site/index.php?/modelgenerator/index

## Config ORM
/application/config/orm.php:
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['orm'] = array(
    'cache' => FALSE,
    'tts' => 3600,
    'autoloadmodel' => TRUE,
    'binary_enable' => FALSE, // MySQL 5.6 minimum
    'encryption_enable' => FALSE, // MySQL 5.6 minimum
    'encryption_key' => "" // MySQL 5.6 minimum
);

```

## Examples
/application/controllers/exemple.php:
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        // ---------- Chargement de la library
        $this->load->library('orm');
        
        // ------------------------------------------------------------------

        // ---------- Exemple création d'un nouvelle object (INSERT)
        $model_user = new \databasename\user_model();
        $model_user->login = 'yoann';
        $model_user->save();
        
        var_dump($model_user);
        
        // ------------------------------------------------------------------
        
        // ---------- Exemple modification de l'object id 100 (UPDATE)
        $user = new \databasename\user_model(100);
        $user->login = 'vanitou';
        $user->save();
        
        // ------------------------------------------------------------------
        
        // ---------- Exemple charge l'object id 100 (SELECT)
        $user = new \databasename\user_model(100);
        
        var_dump($user);
        
        // Autre façon de faire
        $model_user = new \databasename\user_model();
        $user = $model_user->where('id', 100)->find_one();
        
        // Recherche avancé
        $model_user = new \databasename\user_model();
        $users = $model_user->where('login', 'vanitou')->order_by('id', 'ASC')->find();
            
        var_dump($users);
        
        // ------------------------------------------------------------------
        
        // ---------- Exemple suppression de l'object id 100 (DELETE)
        $user = new \databasename\user_model(100);
        $user->remove();
        
        // ---------- Exemple relation
        $user = new \databasename\user_model(100);
        
        // Retourne un object "\databasename\user_group_model"
        $user_group = $user->user_group()->find_one();
        
        var_dump($user, $user_group);
        
        // ------------------------------------------------------------------
        
        // ---------- Exemple validation
        $user = new \databasename\user_model(100);
        $user->firstname = 'Yoann';
        $user->lastname = 'Vanitou';
        
        // Vérifie si l'object est valide
        if ( ! $user->is_validate()) {
            $errors = $user->validate();
            
            // Retourne les champs invalide
            var_dump($errors);
            
        } else {
            // Si l'object est valide on le sauvegarde
            $user->save();
        }
        
        // ------------------------------------------------------------------
                
        // ---------- Exemple transaction automatique
        $this->db_databasename->trans_start();
        
        // Mise à jour de l'object id 100 (UPDATE)
        $user = new \databasename\user_model(100);
        $user->firstname = 'Yoann';
        $user->save();
        
        $this->db_databasename->trans_complete();
        
        // Statut de la transaction
        var_dump($this->db->trans_status());
                
        // ------------------------------------------------------------------
        
        // Affiche les requêtes SQL
        $this->output->enable_profiler(TRUE);
    }

}
```
