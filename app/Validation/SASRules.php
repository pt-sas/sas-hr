<?php

namespace App\Validation;

use Config\Services;
use Config\Database;
use InvalidArgumentException;
use CodeIgniter\Validation\Rules;

class SASRules
{
    /**
     * Checks the database to see if the given value is exists. Can
     * ignore a single record by field/value to make it useful during
     * record updates and check a single record based on another field/value.
     *
     * Example:
     *    is_exist[table.field,ignore_field,ignore_value,another_field,another_value]
     *    is_exist[users.email,id,5,foreign_id,1]
     */
    public function is_exist(?string $str, string $field, array $data): bool
    {
        [$field, $ignoreField, $ignoreValue, $anotherField, $anotherValue] = array_pad(explode(',', $field), 5, null);

        sscanf($field, '%[^.].%[^.]', $table, $field);

        $row = Database::connect($data['DBGroup'] ?? null)
            ->table($table)
            ->select('1')
            ->where($field, $str)
            ->limit(1);

        if (!empty($ignoreField) && !empty($ignoreValue) && !preg_match('/^\{(\w+)\}$/', $ignoreValue)) {
            $row = $row->where("{$ignoreField} !=", $ignoreValue);
        }

        if (!empty($anotherField) && !empty($anotherValue) && !preg_match('/^\{(\w+)\}$/', $anotherValue)) {
            $row = $row->where("{$anotherField}", $anotherValue);
        }

        return $row->get()->getRow() === null;
    }

    /**
     * Check the array data to see if the first data value equal second data.
     * keep a single record by field and value to make it useful
     * 
     * Example:
     *    required_based_field_value[field,keep_value]
     *    is_exist[isfrom,S]
     * 
     * @param string|null $fields
     * @param array $data
     * @return boolean
     */
    public function required_based_field_value($str = null, ?string $fields = null, array $data = []): bool
    {
        $rules = new Rules;

        if (is_null($fields) || empty($data)) {
            throw new InvalidArgumentException('You must supply the parameters: field, data.');
        }

        $fields  = array_map('trim', explode(',', $fields));
        $present = $rules->required($str ?? '');

        if ($present) {
            return true;
        }

        // Index first of array 
        $first = current($fields);
        reset($fields); // Index first
        // Index second of array
        $second = next($fields);

        if (!empty($data[$first]) && $data[$first] === $second)
            return false;

        return true;
    }

    /**
     * Check the array data to see if the first data checkbox value.
     * 
     *
     * @param [type] $str
     * @param string|null $fields
     * @param array $data
     * @return boolean
     */
    public function checkboxes($str = null, ?string $fields = null, array $data): bool
    {
        [$field, $field2] = array_pad(explode(',', $fields), 2, null);

        $isField = ($data[$field] ?? 'N') === 'Y';
        $isField2 = ($data[$field2] ?? 'N') === 'Y';

        // Validasi: tidak boleh keduanya dicentang
        if ($isField && $isField2) {
            return false; // Invalid
        }

        return true; // Valid
    }
}
