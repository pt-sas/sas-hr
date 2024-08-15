<?php

namespace App\Libraries;

use Config\Services;

/**
 * Class to represent Field Name and Retrieve data from database based on column
 *
 * @author Oki Permana
 */
class Field
{
    protected $db;
    protected $validation;

    public function __construct()
    {
        $this->db = db_connect();
        $this->validation = Services::validation();
    }

    /**
     * Retrieve field and data from database
     *
     * @param object $entity
     * @return array
     */
    public function store(object $entity): array
    {
        $result = [];

        $title = $entity->getTitle();
        $table = $entity->getTable();
        $query = $entity->getQuery();
        $data = $entity->getList();
        $column = $entity->getField();
        $primaryKey = $entity->getPrimaryKey();

        $result[] = [
            'field' => 'title',
            'label' => $title ?: ''
        ];

        /**
         * Check generating data using query or modeling data
         * #empty query using modeling data
         */
        $fields = [];

        if (is_null($query))
            $fields = $this->db->getFieldData($table);
        else if (!is_null($query) && is_object($query))
            $fields = $query->getFieldNames();
        else if (!is_null($query) && is_string($query))
            $result = $data;

        if ($fields) {
            foreach ($data as $row) :
                foreach ($fields as $field) :
                    if (is_null($query))
                        $result[] = [
                            'field'         => $field->name,
                            'label'         => $row->{$field->name},
                            'primarykey'    => $field->primary_key == 1 ? true : false
                        ];
                    else
                        $result[] = [
                            'field'         => $field,
                            'label'         => $row->$field,
                            'primarykey'    => $field === $primaryKey ? true : false
                        ];
                endforeach;

                if ($column)
                    if (is_array($column)) {
                        foreach ($column as $val) :
                            $result[] = [
                                'field'         => $val,
                                'label'         => $row->$val,
                                'primarykey'    => false
                            ];
                        endforeach;
                    } else {
                        $result[] = [
                            'field'         => $column,
                            'label'         => $row->$column,
                            'primarykey'    => false
                        ];
                    }
            endforeach;
        }

        return $result;
    }

    /**
     * Get error validation field
     *
     * $table untuk mendapatkan nama table
     * $field_post mendapatkan field dari method post
     * @return $result
     */
    function errorValidation($table, $field_post, $str = null)
    {
        $allError = $this->validation->getErrors();

        $result = [];
        $arrField = [];
        $sparator = '_';

        $result[] = [
            'error' => true,
            'field' => $table
        ];


        $str = empty($str) ? $sparator . 'line' : $sparator . $str;

        // Populate array field from object all error
        foreach ($allError as $field => $msg) :
            if (strpos($field, '.*')) {
                $field_replace = str_replace('.*', '', $field);
                $array = explode('.', $field_replace);

                $arrField[] = end($array);
            }
        endforeach;

        foreach ($field_post as $key => $field) :
            // Check field is array or not
            if (!is_array($field)) {
                // Validation field is not inarray
                if (in_array($key, $arrField)) {
                    $result[] = [
                        'error' => 'error_' . $key,
                        'field' => $key,
                        'label' => $this->validation->getError($key . '.*')
                    ];
                } else {
                    $result[] = [
                        'error' => 'error_' . $key,
                        'field' => $key,
                        'label' => $this->validation->getError($key)
                    ];
                }
            } else {
                foreach ($field as $key2 => $obj) :
                    foreach ($arrField as $row) :
                        $errorField = $key . '.' . $key2 . '.*.' . $row;

                        if (strpos($row, $str))
                            $row = str_replace($str, '', $row);

                        if (!empty($key2) && is_string($key2)) {
                            $result[] = [
                                'error' => 'error_' . $key2,
                                'field' => $row,
                                'label' => $this->validation->getError($errorField)
                            ];
                        }
                    endforeach;
                endforeach;
            }
        endforeach;

        return $result;
    }

    /**
     * Get field element html based on data transfer object
     *
     * @param object $entity
     */
    function fieldTable(object $entity)
    {
        $title = $entity->getTitle();
        $name = $entity->getName();
        $type = $entity->getType();
        $class = $entity->getClass();
        $id = $entity->getId();
        $value = $entity->getValue();
        $required = $entity->getIsRequired();
        $readonly = $entity->getIsReadonly();
        $checked = $entity->getIsChecked();
        $length = $entity->getLength();
        $status = $entity->getStatus();
        $data = $entity->getList();
        $field = $entity->getField();
        $attribute = $entity->getAttribute();

        if (is_null($name) || is_null($type))
            return "";

        $div = '<div class="form-group">';

        $element = '';
        $arrClass = [];

        if (!is_null($class))
            $arrClass = explode(" ", $class);

        if (!is_null($status) && $status === "DR")
            $readonly = null;

        $strAttr = "";
        if (is_array($attribute))
            $strAttr = implode(' ', array_map(
                function ($v, $k) {
                    return sprintf("%s='%s'", $k, $v);
                },
                $attribute,
                array_keys($attribute)
            ));

        if ($type === "checkbox") {
            $div = '<div class="form-check">';
            $element .= '<label class="form-check-label">';

            $element .= '<input type="' . $type . '" class="form-check-input line ' . $class . '" name="' . $name . '" value="' . $value . '" ' . $strAttr . ' ' . $checked . ' ' . $readonly . '>';

            $element .= '<span class="form-check-sign"></span>';
            $element .= '</label>';
            $element .= '</div>';
        } else if ($type === "text") {
            if (in_array("rupiah", $arrClass))
                $class .= " text-right";

            if (in_array("search", $arrClass)) {
                $element .= '<div class="input-icon">
                            <input type="' . $type . '" class="form-control line ' . $class . '" name="' . $name . '" value="' . $value . '" style="width: ' . $length . 'px;" ' . $strAttr . ' ' . $readonly . ' ' . $required . '>
                                <span class="input-icon-addon btn_search">
                                    <i class="fa fa-search"></i>
                                </span>
                            </div>';
            } else {
                $element .= '<input type="' . $type . '" class="form-control line ' . $class . '" name="' . $name . '" value="' . $value . '" style="width: ' . $length . 'px;" ' . $strAttr . ' ' . $readonly . ' ' . $required . '>';
            }
        } else if ($type === "select") {
            $element .= '<select class="form-control line ' . $class . '" name="' . $name . '" style="width: ' . $length . 'px;" ' . $strAttr . ' ' . $readonly . ' ' . $required . '>';
            $element .= '<option value=""></option>';

            if (!$arrClass)
                return "";

            if (in_array("select2", $arrClass)) {
                if (!is_array($field))
                    return "";

                foreach ($data as $val) :
                    $fieldName = $val->{$field['text']};

                    if (isset($field['text2']))
                        $fieldName .= " (" . $val->{$field['text2']} . ")";

                    // Check default value is not null and default value equal $field
                    if (!is_null($value) && ((is_string($value) && strtoupper($value) === strtoupper($fieldName)) || ($value == $val->{$field['id']})))
                        $element .= '<option value="' . $val->{$field['id']} . '" selected>' . $fieldName . '</option>';
                    else
                        $element .= '<option value="' . $val->{$field['id']} . '">' . $fieldName . '</option>';
                endforeach;
            }

            if (!is_null($id))
                $element .= '<option value="' . $id . '" selected>' . $value . '</option>';

            $element .= '</select>';
        } else if ($type === 'button') {
            $refValue = null;

            if (in_array("reference-key", $arrClass)) {
                $refValue = $value;
                $value = null;
            }

            if (in_array("delete", $arrClass)) {
                $class .= ' btn-danger btn_delete';
                $icon = '<i class="fas fa-trash-alt"></i>';
                $title = "Delete";

                $element .= '<button type="button" title="' . $title . '" class="btn btn-link line ' . $class . '" id="' . $value . '" name="' . $name . '" value="' . $refValue . '">
                                        ' . $icon . '
                                        </button>';
            } else if (in_array("numeric", $arrClass)) {
                $value = $value ?? 0;

                $element .= '<button type="button" title="' . $title . '" class="btn btn-sm btn-round line ' . $class . '" id="' . $id . '" name="' . $name . '" value="' . $value . '" ' . $readonly . '> ' . $value . ' </button>';
            }
        }

        $div .= $element;

        $div .= '</div>';

        return $div;
    }

    /**
     * Function to add array key to more than one data
     *
     * @param [type] $table
     * @param [type] $data
     * @param string $field
     * @param [type] $value
     * @param [type] $text
     * @param array $array
     */
    public function setDataSelect($table, $data, $field = 'id', $value, $text, array $array = [])
    {
        if ($array) {
            $value = null;
            $value = (array) $value;

            foreach ($array as $row) :
                $value[] = $row->{$field};
            endforeach;
        }

        foreach ($data as $row) :
            if ($this->db->fieldExists($field, $table))
                $row->{$field} = ([
                    'id'    => $value,
                    'name'  => $text
                ]);
            else
                $row->{$field} = ([
                    'id'    => $value,
                    'name'  => $text
                ]);
        endforeach;

        return $data;
    }

    /**
     * Function for merge object to array object
     *
     * @param [type] $arr
     * @param array $data array object
     * @return void
     */
    public function mergeArrObject($arr, $data = [])
    {
        foreach ($arr as $key => $value) :
            $row = $value;

            if (count($data) > 0)
                foreach ($data as $field => $val) :
                    $row->$field = $val;
                endforeach;

            $arr[$key] = $row;
        endforeach;

        return $arr;
    }
}
