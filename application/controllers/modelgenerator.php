<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Modelgenerator extends CI_Controller {

	private $model = '';

	public function __construct() {
		parent::__construct();
	}

	public function index() {
		$this->load->database();

		$query_table = $this->db->query('SHOW TABLE STATUS');

		foreach ($query_table->result_array() as $table) {
			$this->model = '';
			$this->_append("<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');"."\r\n");
			$this->_append("\r\n");
			$this->_append('class '.$table['Name'].'_model extends Orm {'."\r\n");
			$this->_append("\r\n");
			$this->_append("\t".'public static $table = \''.$table['Name'].'_model\';'."\r\n");
			$this->_append("\r\n");

			$query_field = $this->db->query('SHOW COLUMNS FROM '.$table['Name']);

			if ($query_field->num_rows() > 0) {
				$primary_keys = "";

				$foreign_keys = "\t".'public static $foreign_key = array('."\r\n";
				$flag_foreign_keys = false;

				$this->_append("\t".'public static $fields = array('."\r\n");

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
							$type[0] = 'datetime';
							break;
						case 'int':
							$type[0] = 'int';
							break;
						default:
							$type[0] = 'string';
							break;
					}

					$this->_append("\t\t".'\''.$field['Field'].'\' => \''.$type[0].(($field['Null'] == 'YES') ? "|NULL'" : "'").",\r\n");
					if ($field['Key'] == 'PRI') {
						$primary_keys .= "\t".'public static $primary_key = \''.$field['Field'].'\';'."\r\n\r\n";
					}
					if ($field['Key'] == 'MUL') {
						$foreign_keys .= "\t\t".'"'.$field['Field'].'"'.",\r\n";
						$flag_foreign_keys = true;
					}
				}
				$this->_append("end");
				$this->model = str_replace(",\r\nend", "", $this->model);
				$this->_append("\r\n\t".');'."\r\n");
				if ($flag_foreign_keys) {
					$foreign_keys.="end";
					$foreign_keys = str_replace(",\r\nend", "", $foreign_keys);
				}
				$foreign_keys.="\r\n\t);\r\n";
				$this->_append($primary_keys);
				$this->_append($foreign_keys);
			}
			$this->_append("\r\n");

			/**
			 * AJOUT DES RELATION PAR CASE
			 */
			// création des relations (clés étrangères d'inno db préalablement définies en base)
			$sql_foreign_keys = 'SELECT TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME 
                                    FROM information_schema.KEY_COLUMN_USAGE 
                                    WHERE CONSTRAINT_NAME != "PRIMARY"
                                        AND  TABLE_NAME = "'.$table['Name'].'"';
			$query_relations = $this->db->query($sql_foreign_keys);
			
			if ($query_relations->num_rows() > 0) {
				$this->_append("\t".'public static $relation = array('."\r\n");
				
				foreach ($query_relations->result_array() as $data) {
					$relation_name = str_replace("id_", "", $data["COLUMN_NAME"]);
					$referenced_table_name = $data["REFERENCED_TABLE_NAME"];
					$referenced_column_name = $data["REFERENCED_COLUMN_NAME"];
					$column_name = $data["COLUMN_NAME"];
					$new_relation = "\t\t".'"'.$relation_name.'" => array("has_one", "'.$referenced_table_name.'", "'.$referenced_column_name.'", "'.$column_name.'")';
					$this->_append($new_relation.",\r\n");
				}
				
				$this->_append("end");
				$this->model = str_replace(",\r\nend", "", $this->model);
				$this->_append("\r\n\t);\r\n");
			}

			$this->_append('}'."\r\n");
			$this->_append("\r\n");
			$fp = fopen(FCPATH.APPPATH.'models/'.$table['Name'].'_model.php', 'w');
			fputs($fp, $this->model);
			fclose($fp);
		}
		
		$this->output->set_output("DONE ;)");
	}

	private function _append($output) {
		$this->model .= $output;
	}

}

/* End of file model.php */
/* Location: ./application/controllers/model.php */