<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * SAG ORM (objet relationnel mapping)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/maltyxx/sag-orm
 * @version 3.1 (20140710)
 */
class Orm_field {

    const TYPE_INTEGER = 'int';
    const TYPE_INT = self::TYPE_INTEGER;
    const TYPE_FLOAT = 'float';
    const TYPE_DOUBLE = self::TYPE_FLOAT;
    const TYPE_STRING = 'string';
    const TYPE_DATE = self::TYPE_STRING;
    const TYPE_DATETIME = self::TYPE_STRING;
    const ALLOWNULL = 'allow_null';
    const ENCRYPT = 'encrypt';
    const BINARY = 'binary';

    public $name;
    public $type;
    public $date_format;
    public $allow_null;
    public $encrypt;
    public $binary;
    public $default_value;
    public $value;

    public function __construct(array $config, $value = '') {
        foreach ($config as $config_key => $config_value) {
            $this->{$config_key} = $config_value;
        }

        $this->value = $value;

        if (empty($this->type))
            $this->type = self::TYPE_STRING;

        if (empty($this->date_format))
            $this->date_format = FALSE;

        if (empty($this->encrypt))
            $this->encrypt = FALSE;

        if (empty($this->binary))
            $this->binary = FALSE;

        if (empty($this->allow_null))
            $this->allow_null = FALSE;

        if (empty($this->default_value))
            $this->default_value = FALSE;
    }

    public function convert() {
        if (!empty($this->default_value) && empty($this->value)) {
            return $this->value = $this->default_value;
        }

        if ($this->allow_null === TRUE && empty($this->value)) {
            return $this->value = NULL;
        }

        if (in_array($this->type, array(self::TYPE_DATE, self::TYPE_DATETIME)) && !empty($this->value) && !empty($this->date_format)) {
            return $this->value = date($this->date_format, strtotime($this->value));
        }

        switch (strtolower($this->type)) {
            case self::TYPE_INTEGER:
                settype($this->value, 'integer');
                break;
            case self::TYPE_FLOAT:
                settype($this->value, 'float');
                break;
            default:
                settype($this->value, 'string');
        }

        return $this->value;
    }

}

/* End of file Orm_field.php */
/* Location: ./application/libraries/Orm_field.php */
