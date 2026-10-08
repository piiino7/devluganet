<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserMigration extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('users', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 50])
            //->addColumn('email', 'string', ['limit' => 150])
            ->addColumn('password', 'string', ['limit' => 255])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addTimestamps()
            //->addIndex(['email'], ['unique' => true])
            ->addIndex(['name'], ['unique' => true])
            ->addIndex(['is_active'], ['name' => 'idx_users_is_active'])
            ->addIndex(['deleted_at'], ['name' => 'idx_users_deleted_at'])
            ->create();
    }
}
