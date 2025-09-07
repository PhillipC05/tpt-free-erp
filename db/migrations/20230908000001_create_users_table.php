<?php

use Phinx\Migration\AbstractMigration;

class CreateUsersTable extends AbstractMigration
{
    public function change()
    {
        $sql = file_get_contents(__DIR__ . '/../schemas/001_users.sql');
        $this->execute($sql);
    }
}
