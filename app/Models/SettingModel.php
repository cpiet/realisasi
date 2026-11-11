<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['key', 'value', 'description'];
    protected $useTimestamps    = true;

    public static function getItem(string $key, $default = null)
    {
        $m = new self();
        $row = $m->where('key', $key)->first();
        return $row ? $row['value'] : $default;
    }

    public static function setItem(string $key, $value, $description = null)
    {
        $m = new self();
        $row = $m->where('key', $key)->first();
        if ($row) {
            $m->update($row['id'], ['value' => $value]);
        } else {
            $m->insert([
                'key'         => $key,
                'value'       => $value,
                'description' => $description,
            ]);
        }
    }
}
