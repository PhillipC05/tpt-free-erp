<?php

use Phinx\Seed\AbstractSeed;

class UsersTableSeeder extends AbstractSeed
{
    public function run()
    {
        $data = [
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'email' => 'admin@tpt-erp.com',
                'username' => 'admin',
                'password_hash' => password_hash('password', PASSWORD_DEFAULT),
                'first_name' => 'Admin',
                'last_name' => 'User',
                'is_active' => true,
                'is_verified' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        ];

        $users = $this->table('users');
        $users->insert($data)->saveData();
    }
}
