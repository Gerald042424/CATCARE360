<?php

namespace App\Models;

use CodeIgniter\Model;

class StaffModel extends Model
{
    protected $table = 'staff';
    protected $allowedFields = ['name', 'email', 'password_hash', 'role', 'is_active'];

    /** @return list<string> */
    public function activeAdminEmailAddresses(): array
    {
        $admins = $this->select('email')->where('role', 'admin')->where('is_active', 1)->findAll();
        $recipients = [];
        foreach ($admins as $admin) {
            $address = strtolower(trim((string) $admin['email']));
            if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
                $recipients[$address] = $address;
            }
        }
        return array_values($recipients);
    }
}
