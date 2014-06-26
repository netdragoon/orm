<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class model extends Orm {
    const STATUT_ACTIF = '1';
    const STATUT_INACTIF = '2';
    const STATUT_SUPPRIMER = '3';
    
    public static $table = 'oeuvre';
    
    public static $primary_key = 'id';
    
    public static $fields = array(
        array('name' => 'id', 'type' => parent::FIELD_TYPE_INT),
    );
    
    public static $associations = array(
        array('association_key' => 'client', 'type' => Orm_association::TYPE_BELONGS_TO, 'model' => 'client_model', 'primary_key' => 'id', 'foreign_key' => 'artiste_id')
    );
    
    public static $validations = array(
        array('field' => 'artiste_id', 'type' => Orm_validation::TYPE_PRESENCE)
    );
    
}
