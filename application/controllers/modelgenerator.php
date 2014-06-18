<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Willy & Case
 * has_one      : un utilisateur n'a qu'une nationnalité (id_nationnalité dans users)
 * has_many     : un groupe a plusieurs membres
 * belongs_to   : plusieurs vidéos appartiennent à une utilisateur (id_user dans users_videos)
 */
class Modelgenerator extends CI_Controller {

	private $model = '';

	const STARTCODE2KEEP = "//--START_PERSISTANT_CODE";
	const ENDCODE2KEEP = "//--END_PERSISTANT_CODE";

	public function __construct() {
		parent::__construct();
	}

	public function index() {

		$this->load->database();
		$relations_comments = array();
		$code2keep = array();
		$stockRelation = array();
		$stockRelation_javadoc = array();
		$relation_inverse = array(
			"belongs_to" => "has_many",
			"has_many" => "belongs_to",
			"has_one" => "has_one"
		);

		/* ---------------------------------- */
		/*      CLEAN DES MODELES           */
		/* ---------------------------------- */
		echo '<pre>';
		echo '<h1>CLEAN</h1>';
		// sélection de tous les modèles
		$files = glob(FCPATH.APPPATH.'models/kraken/*');
		foreach ($files as $file) { // iterate files
			if (is_file($file)) {
				$filename = str_replace(FCPATH.APPPATH.'models/kraken/', "", $file);

				// on ouvre le fichier et on isole le code à ne pas toucher
				$content = file_get_contents($file);
				$particules = explode(self::STARTCODE2KEEP, $content);
				if (!empty($particules[1])) {
					$particules = explode(self::ENDCODE2KEEP, $particules[1]);
					$codePerso = $particules[0];
				} else {
					$codePerso = "";
				}
				if (!empty($codePerso)) {
					$code2keep[$filename] = "\r\n\t".self::STARTCODE2KEEP.($codePerso)."".self::ENDCODE2KEEP."\r\n";
				}

				echo 'Suppression du fichier <b>'.$filename.'</b> du rep kraken : ';
				if (unlink($file)) {
					echo '<b style="color:green">OK</b><br />';
				} else {
					echo '<b style="color:red">KO</b><br />';
				}
			}
		}

		/* ---------------------------------------- */
		/*    STOCKAGE DES RELATIONS INVERSES       */
		/* ---------------------------------------- */

		$query_table = $this->db->query('SHOW TABLE STATUS');
		foreach ($query_table->result_array() as $table) {
			// création des relations (clés étrangères d'inno db préalablement définies en base)
			$sql_foreign_keys = 'SELECT DISTINCT(CONSTRAINT_NAME),TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
                                    FROM information_schema.KEY_COLUMN_USAGE
                                    WHERE CONSTRAINT_NAME != "PRIMARY"
                                        AND  TABLE_NAME = "'.$table['Name'].'"';

			$query_relations = $this->db->query($sql_foreign_keys);

			if ($query_relations->num_rows() > 0) {

				foreach ($query_relations->result_array() as $data) {
					$referenced_table_name = $data["REFERENCED_TABLE_NAME"];
					$relation_type = "has_one"; // le plus courant

					$query_field = $this->db->query('SHOW FULL COLUMNS FROM `'.$table['Name'].'`');
					$relations_comments = array();
					if ($query_field->num_rows() > 0) {
						foreach ($query_field->result_array() as $field) {
							$relations_comments[$field['Field']] = $field['Comment'];
						}
					}

					if (!empty($relations_comments[$data["COLUMN_NAME"]])) {
						$relation_type = $relations_comments[$data["COLUMN_NAME"]];

						if ($relation_type != "has_one" && $relation_type != "has_many" && $relation_type != "belongs_to") {
							$relation_type = "";
						}
					}

					if ($relation_type != "" && strtolower($relation_type) != "has_one" && empty($already_seen[$referenced_table_name][$table['Name']])) {
						$already_seen[$referenced_table_name][$table['Name']] = TRUE;

						// STOCKAGE DES RELATION INVERSES						
						$referenced_table_name_t = strtolower($table['Name']);
						$stockRelation[$referenced_table_name][] = "\t\t'$referenced_table_name_t' => array('{$relation_inverse[$relation_type]}', '{$table['Name']}', '{$data["COLUMN_NAME"]}', 'id'),\r\n";
						$stockRelation_javadoc[$referenced_table_name][] = "\t * @method {$referenced_table_name_t}_model $referenced_table_name_t() {$relation_inverse[$relation_type]}\r\n";
					}
				}
			}
		}

		//print_r($stockRelation);
		echo '<hr />';
		/* ---------------------------------- */
		/*      RE-CREATION DES MODELES       */
		/* ---------------------------------- */
		echo '<h1>GENERATION</h1>';
		foreach ($query_table->result_array() as $table) {
			$this->model = '';

			$this->_append("<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');"."\r\n");
			$this->_append("\r\n");
			$this->_append('class '.$table['Name'].'_model extends Orm_model {'."\r\n");
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
			if ($query_relations->num_rows() > 0 || (isset($stockRelation[$table['Name']]) && count($stockRelation[$table['Name']])) > 0) {
				$asso = true;

				$relations_javadoc_buffer = "\t/**\r\n";
				$relations_buffer = "\t public static \$relations = array(\r\n";
				// écriture relation inverses
				if (isset($stockRelation[$table['Name']])) {
					foreach ($stockRelation[$table['Name']] as $rel) {
						$relations_buffer .= $rel;
					}

					foreach ($stockRelation_javadoc[$table['Name']] as $rel_j) {
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
						$relations_javadoc_buffer .= "\t * @method {$referenced_table_name}_model $referenced_table_name() $relation_type\r\n";
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
			// ajout du code à garder
			if (!empty($code2keep[$filename])) {
				$this->_append($code2keep[$filename]."\r\n");
			}
			$this->_append('}'."\r\n");
			$this->_append("\r\n");
			$fp = fopen(FCPATH.APPPATH.'models/kraken/'.$filename, 'w+');
			echo 'Creation du fichier : <b>'.$filename.'</b> : <b style="color:green">OK</b><br />';
			fputs($fp, $this->model);
			fclose($fp);
		}

		echo '<hr>';
		$this->output->set_output("<h2>DONE ;)</h2>");
	}

	private function _append($output) {
		$this->model .= $output;
	}

}

/* End of file model.php */
/* Location: ./application/controllers/model.php */
