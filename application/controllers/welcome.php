<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        // ---------- Chargement de la library
        $this->load->library('orm');

        // ---------- Création d'un nouvelle object (INSERT)
        $model_user = new \dbd\user_model();
        $model_user->login = 'yoann';
        $model_user->save();
        
        var_dump($model_user);
        
        // ---------- Modification de l'object id 1 (UPDATE)
        $model_user = new \dbd\user_model(1);
        $model_user->login = 'vanitou';
        $model_user->save();
        
        // ---------- Charge l'object id 1 (SELECT)
        $model_user = new \dbd\user_model(1);
        
        var_dump($model_user);
        
        // Autre façon de faire
        $model_user = new \dbd\user_model();
        $model_user = $model_user->where('id', 1)->find_one();
        
        // Recherche avancé
        $model_user = new \dbd\user_model();
        $model_user = $model_user->where('login', 'vanitou')->order_by('id', 'ASC')->find();
            
        var_dump($model_user);
        
        // ---------- Suppression de l'object id 1 (DELETE)
        $model_user = new \dbd\user_model(1);
        $model_user->remove();
        
        // ---------- Les relations (JOINT)
        $model_user = new \dbd\user_model(1);
        
        // Retourne un object "\dbd\user_group_model"
        $model_user_group = $model_user->user_group()->find_one();
        
        var_dump($model_user, $model_user_group);

        // Affiche les requêtes SQL
        $this->output->enable_profiler(TRUE);
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
