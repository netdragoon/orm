<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Willy & Case & Yoann
 * has_one      : un utilisateur n'a qu'une nationnalité (id_nationnalité dans users)
 * has_many     : un groupe a plusieurs membres
 * belongs_to   : plusieurs vidéos appartiennent à une utilisateur (id_user dans users_videos)
 */
class Modelgenerator extends CI_Controller {

	private $override = array();
	private $association = array();
	private $model_output = '';

	const STARTCODE2KEEP = "//--START_PERSISTANT_CODE";
	const ENDCODE2KEEP = "//--END_PERSISTANT_CODE";

	public function __construct() {
		parent::__construct();
	}

	private function _config() {
		// Fichier de configuration des bases de données
		$file_db_env = APPPATH.'config/'.ENVIRONMENT.'/database.php';
		$file_db = APPPATH.'config/database.php';
		$file_path = (file_exists($file_db_env)) ? $file_db_env : $file_db;

		// Inclusion de la configuration
		require_once($file_path);

		// Retourne la configuration
		return $db;
	}

	private function _dir($dir) {
		if ( ! file_exists($dir)) {
			if ( ! mkdir($dir, 0644)) {
				return FALSE;
			}
		}

		return TRUE;
	}

	private function _save_override($dir) {
		
		echo '<pre>';
		echo '<h1>CLEAN</h1>';
		
		foreach (glob($dir) as $file_path) {
			if (is_file($file_path)) {
				$file = basename($file_path, '.php');
				$path = dirname($file_path);
				$file_content = file_get_contents($file_path);

				$particules = explode(self::STARTCODE2KEEP, $file_content);

				if ( ! empty($particules[1])) {
					$particules = explode(self::ENDCODE2KEEP, $particules[1]);
					$override = $particules[0];
				} else {
					$override = '';
				}

				if ( ! empty($override)) {
					$this->override[$file] = "\r\n\t".self::STARTCODE2KEEP.($override)."".self::ENDCODE2KEEP."\r\n";
				}

				echo "Suppression du fichier <b>$file</b> du répertoire $path";

				if (unlink($file_path)) {
					echo '<b style="color:green">OK</b><br />';
				} else {
					echo '<b style="color:red">KO</b><br />';
				}
			}
		}
	}

	private function _association_many($namespace) {	
		$relation_inverse = array(
			"belongs_to" => "has_many",
			"has_many" => "belongs_to",
			"has_one" => "has_one"
		);
		
		$query_table = $this->db->query("SHOW TABLE STATUS");
		
		foreach ($query_table->result_array() as $table) {
			// création des relations (clés étrangères d'inno db préalablement définies en base)
			$sql = 'SELECT DISTINCT(CONSTRAINT_NAME),TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
									FROM information_schema.KEY_COLUMN_USAGE
									WHERE CONSTRAINT_NAME != "PRIMARY"
										AND  TABLE_NAME = "'.$table['Name'].'"';

			$query_relations = $this->db->query($sql);

			if ($query_relations->num_rows() > 0) {
				foreach ($query_relations->result_array() as $data) {
					$referenced_table_name = $data["REFERENCED_TABLE_NAME"];
					
					 // le plus courant
					$relation_type = "has_one";
					
					$query_field = $this->db->query('SHOW FULL COLUMNS FROM `'.$table['Name'].'`');
					$relations_comments = array();
					
					if ($query_field->num_rows() > 0) {
						foreach ($query_field->result_array() as $field) {
							$relations_comments[$field['Field']] = $field['Comment'];
						}
					}

					if ( ! empty($relations_comments[$data["COLUMN_NAME"]])) {
						$relation_type = $relations_comments[$data["COLUMN_NAME"]];

						if ($relation_type != "has_one" && $relation_type != "has_many" && $relation_type != "belongs_to") {
							$relation_type = "";
						}
					}

					if ($relation_type != "" && strtolower($relation_type) != "has_one" && empty($already_seen[$referenced_table_name][$table['Name']])) {
						$already_seen[$referenced_table_name][$table['Name']] = TRUE;

						// STOCKAGE DES RELATION INVERSES						
						$referenced_table_name_t = strtolower($table['Name']);
						$this->association[$referenced_table_name]['php'][] = "\t\t'$referenced_table_name_t' => array('{$relation_inverse[$relation_type]}', '{$table['Name']}', '{$data["COLUMN_NAME"]}', 'id'),\r\n";
						$this->association[$referenced_table_name]['javadoc'][] = "\t * @method $namespace\\{$referenced_table_name_t}_model $referenced_table_name_t() {$relation_inverse[$relation_type]}\r\n";
					}
				}
			}
		}
		
		echo '<hr />';
	}
	
	private function _create_model($namespace) {		
		$relations_comments = array();
		
		echo '<h1>GENERATION</h1>';

		$query_table = $this->db->query("SHOW TABLE STATUS");
		
		foreach ($query_table->result_array() as $table) {
			$this->model_output = '';

			$this->_append("<?php\r\n");
			$this->_append("namespace $namespace;\r\n");
			$this->_append("\r\n");
			$this->_append("if ( ! defined('BASEPATH')) exit('No direct script access allowed');\r\n");
			$this->_append("\r\n");
			$this->_append("class {$table['Name']}_model extends \Orm_model {\r\n");
			$this->_append("\r\n");

			//on va gerer pour les table d'enum, les constantes sur les modeles codigniter
			if (strpos($table['Name'], "enum") === 0 && $table['Name'] != "enumregime") {
				$this->_append("\r\n");
				$enums = array();
				$query_enum = $this->db->query('SELECT * FROM '.$table['Name']);
				if ($query_enum->num_rows() > 0) {
					foreach ($query_enum->result_array() as $val) {
						$id = $val['id'];
						$firstpass = true;
						foreach ($val as $k => $v) {
							if ($firstpass) {
								$firstpass = false;
							} else {
								$this->_append("\t const ".strtoupper($k)."_".str_replace('+', '_', str_replace('-', '_', str_replace(' ', '_', strtoupper($v))))." = ".$id."; "."\r\n");
							}
						}
					}
				}
				$this->_append("\r\n");
			}

			$this->_append("\t".'public static $table = \''.$table['Name'].'\';'."\r\n");
			$this->_append("\r\n");

			$query_field = $this->db->query('SHOW FULL COLUMNS FROM `'.$table['Name'].'`');

			if ($query_field->num_rows() > 0) {

				$primary_keys = "";

				$foreign_keys = "\t".'public static $foreign_key = array('."\r\n";
				$flag_foreign_keys = false;

				$fields_javadoc_buffer = "\t/**\r\n";
				$fields_buffer = "\tpublic static \$fields = array(\r\n";

				foreach ($query_field->result_array() as $field) {
					$type = explode('(', $field['Type']);

					switch ($type[0]) {
						case 'bigint':
						case 'mediumint':
						case 'tinyint':
						case 'smallint':
						case 'int':
							$type[0] = 'int';
							break;
						case 'float':
						case 'double':
							$type[0] = 'float';
							break;
						case 'timestamp':
						case 'date':
						case 'datetime':
						default:
							$type[0] = 'string';
							break;
					}
					// on stocke les commentaires (notamment pour les clés étrangères
					$relations_comments[$field['Field']] = $field['Comment'];

					//Gestion des description des champs dans le commentaire de celui ci
					//Exemple encrypt ou has_one pour les relations

					$valeurfield = '';
					if ($field['Null'] == 'YES')
						$valeurfield .= '|NULL';

					$tabField = explode('|', $field['Comment']);
					foreach ($tabField as $fieldAjout) {
						switch ($fieldAjout) {
							case 'encrypt':
								$valeurfield .= '|encrypt';
								break;
						}
					}

					$fields_javadoc_type = ($type[0] === "int") ? "integer" : $type[0];
					$fields_javadoc_buffer .= "\t * @property $fields_javadoc_type \${$field['Field']}\r\n";
					$fields_buffer .= "\t\t'{$field['Field']}' => '{$type[0]}$valeurfield',\r\n";

					if ($field['Key'] == 'PRI') {
						$primary_keys .= "\r\n\t".'public static $primary_key = \''.$field['Field'].'\';'."\r\n";
					}
					if ($field['Key'] == 'MUL') {
						$foreign_keys .= "\t\t".'"'.$field['Field'].'"'.",\r\n";
						$flag_foreign_keys = true;
					}
				}

				$fields_javadoc_buffer .= "\t */\r\n";

				$this->_append($fields_javadoc_buffer);

				$fields_buffer .= "\t);\r\n";
				$fields_buffer = str_replace(",\r\n\t);", "\r\n\t);", $fields_buffer);

				$this->_append($fields_buffer);

				if ($flag_foreign_keys) {
					$foreign_keys.="end";
					$foreign_keys = str_replace(",\r\nend", "", $foreign_keys);
				}
				$foreign_keys.="\r\n\t);\r\n";
				$this->_append($primary_keys);

				//$this->_append($foreign_keys);
			}

			$this->_append("\r\n");

			/* ---------------------------------------- */
			/*    ECRITURE DES RELATIONS                */
			/* ---------------------------------------- */
			// création des relations (clés étrangères d'inno db préalablement définies en base)
			$sql_foreign_keys = 'SELECT DISTINCT(CONSTRAINT_NAME),TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
                                    FROM information_schema.KEY_COLUMN_USAGE
                                    WHERE CONSTRAINT_NAME != "PRIMARY"
                                        AND  TABLE_NAME = "'.$table['Name'].'"';
			$query_relations = $this->db->query($sql_foreign_keys);
			$asso = false;
						
			if ($query_relations->num_rows() > 0 || (isset($this->association[$table['Name']]) && count($this->association[$table['Name']])) > 0) {
				$asso = true;

				$relations_javadoc_buffer = "\t/**\r\n";
				$relations_buffer = "\t public static \$relations = array(\r\n";
				// écriture relation inverses
				if (isset($this->association[$table['Name']])) {
					foreach ($this->association[$table['Name']]['php'] as $rel) {
						$relations_buffer .= $rel;
					}

					foreach ($this->association[$table['Name']]['javadoc'] as $rel_j) {
						$relations_javadoc_buffer .= $rel_j;
					}
				}

				foreach ($query_relations->result_array() as $data) {

					$relation_name = str_replace("_id", "", $data["COLUMN_NAME"]);
					$referenced_table_name = $data["REFERENCED_TABLE_NAME"];
					$referenced_column_name = $data["REFERENCED_COLUMN_NAME"];
					$column_name = $data["COLUMN_NAME"];
					$relation_type = 'has_one'; // le plus courant

					if (!empty($relations_comments[$data["COLUMN_NAME"]])) {
						$relation_type = $relations_comments[$data["COLUMN_NAME"]];

						// sécurité
						if ($relation_type != 'has_one' && $relation_type != 'has_many' && $relation_type != 'belongs_to') {
							$relation_type = '';
						}
					}

					if ($relation_type != "") {
						$relations_javadoc_buffer .= "\t * @method $namespace\\{$referenced_table_name}_model $referenced_table_name() $relation_type\r\n";
						$relations_buffer .= "\t\t'$referenced_table_name' => array('$relation_type', '$referenced_table_name', '$referenced_column_name', '$column_name'),\r\n";
					}
				}

				$relations_buffer .= "\t);\r\n";
				$relations_buffer = str_replace(",\r\n\t);", "\r\n\t);", $relations_buffer);

				$relations_javadoc_buffer .= "\t */\r\n";
				
				$this->_append($relations_javadoc_buffer);
				$this->_append($relations_buffer);
			}
			
			$filename = $table['Name'].'_model.php';
			
			// Si il exite un override
			if ( ! empty($this->override[$filename])) {
				$this->_append($this->override[$filename]."\r\n");
			}
			
			$this->_append('}'."\r\n");
			$this->_append("\r\n");
						
			$fp = fopen(FCPATH.APPPATH.'models/'.$namespace.'/'.$filename, 'w+');
			
			echo 'Creation du fichier : <b>'.$filename.'</b> : <b style="color:green">OK</b><br />';
			
			fputs($fp, $this->model_output);
			fclose($fp);
		}
		
		$this->association = array();
		$this->override = array();

		echo '<hr />';
		echo '<h2>DONE ;)</h2>';
	}

	public function run() {
		$config = $this->_config();

		foreach ($config as $namespace => $db) {
			// Création du répertoire
			$this->_dir(APPPATH.'models/'.$namespace);

			// Récupère les données des anciens modèles
			$this->_save_override(APPPATH.'models/'.$namespace.'/*');

			// Stock la nouvelle connexion à la base de donnée
			$this->load->database($namespace);
			
			// Stock les association many
			$this->_association_many($namespace);
			
			// Création des modèles
			$this->_create_model($namespace);
		}
	}

	private function _append($output) {
		$this->model_output .= $output;
	}

}

/* End of file model.php */
/* Location: ./application/controllers/model.php */
