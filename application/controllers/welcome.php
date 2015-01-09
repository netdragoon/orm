<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        // ---------- Chargement de la library
        $this->load->library('orm');

        // ---------- Exemple création d'un nouvelle object (INSERT)
        $model_user = new \dbd\user_model();
        $model_user->login = 'yoann';
        $model_user->save();
        
        var_dump($model_user);
        
        // ---------- Exemple modification de l'object id 100 (UPDATE)
        $user = new \dbd\user_model(100);
        $user->login = 'vanitou';
        $user->save();
        
        // ---------- Exemple charge l'object id 100 (SELECT)
        $user = new \dbd\user_model(100);
        
        var_dump($user);
        
        // Autre façon de faire
        $model_user = new \dbd\user_model();
        $user = $model_user->where('id', 100)->find_one();
        
        // Recherche avancé
        $model_user = new \dbd\user_model();
        $users = $model_user->where('login', 'vanitou')->order_by('id', 'ASC')->find();
            
        var_dump($users);
        
        // ---------- Exemple suppression de l'object id 1 (DELETE)
        $user = new \dbd\user_model(100);
        $user->remove();
        
        // ---------- Exemple relation
        $user = new \dbd\user_model(100);
        
        // Retourne un object "\dbd\user_group_model"
        $user_group = $user->user_group()->find_one();
        
        var_dump($user, $user_group);
        
        // ---------- Exemple validation
        $user = new \dbd\user_model(100);
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
                
        // ---------- Exemple transaction automatique
        $this->db_dbd->trans_start();
        
        // Suppression de l'object id 1 (DELETE)
        $user = new \dbd\user_model(100);
        $user->firstname = 'Yoann';
        $user->save();
        
        $this->db_dbd->trans_complete();
        
        // Statut de la transaction
        var_dump($this->db->trans_status());
        
        // ---------- Exemple transaction manuelle
        $this->db_dbd->trans_begin();
        
        // Suppression de l'object id 1 (DELETE)
        $user = new \dbd\user_model(100);
        $user->firstname = 'Yoann';
        $user->save();
        
        // Statut de la transaction
        if ($this->db->trans_status() === FALSE) {
            // Annule la transaction
            $this->db->trans_rollback();
        } else {
            // Valide la transaction
            $this->db->trans_commit();
        }
        
        // Affiche les requêtes SQL
        $this->output->enable_profiler(TRUE);
    }
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
