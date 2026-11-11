<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateUserPinsToSixDigits extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('users') && $this->db->fieldExists('login_token', 'users')) {
            $users = $this->db->table('users')->get()->getResultArray();
            $usedPins = [];

            foreach ($users as $user) {
                $pin = (string)($user['login_token'] ?? '');
                // Check if already 6 digits
                if (!preg_match('/^\d{6}$/', $pin) || in_array($pin, $usedPins, true)) {
                    if ($user['username'] === 'admin') {
                        $newPin = '123456';
                    } elseif ($user['username'] === 'stafren') {
                        $newPin = '654321';
                    } else {
                        do {
                            $newPin = (string)random_int(100000, 999999);
                        } while (in_array($newPin, $usedPins, true));
                    }

                    $usedPins[] = $newPin;
                    $this->db->table('users')->where('id', $user['id'])->update(['login_token' => $newPin]);
                } else {
                    $usedPins[] = $pin;
                }
            }
        }
    }

    public function down()
    {
        // No down migration needed for PIN values
    }
}
