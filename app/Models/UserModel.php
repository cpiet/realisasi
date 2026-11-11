<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['username', 'password_hash', 'name', 'role', 'login_token', 'is_active', 'last_login_at'];
    protected $useTimestamps    = true;

    /**
     * Generate 6-digit random numeric PIN unik
     */
    public function generateUniquePin(int $digits = 6): string
    {
        do {
            $min = (int)(10 ** ($digits - 1));
            $max = (int)((10 ** $digits) - 1);
            $pin = (string)random_int($min, $max);
        } while ($this->where('login_token', $pin)->countAllResults() > 0);

        return $pin;
    }

    /**
     * Backward-compatible token generator (mengembalikan 6-digit PIN numerik)
     */
    public function generateUniqueToken(): string
    {
        return $this->generateUniquePin(6);
    }
}
