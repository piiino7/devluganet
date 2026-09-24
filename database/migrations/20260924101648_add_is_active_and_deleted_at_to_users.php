<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddIsActiveAndDeletedAtToUsers extends AbstractMigration
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
        $this->table('users')
            ->addColumn('is_active', 'boolean', ['default' => true, 'after' => 'password'])
            ->addColumn('deleted_at', 'datetime', ['null' => true, 'after' => 'is_active'])
            ->addIndex(['is_active'], ['name' => 'idx_users_is_active'])
            ->addIndex(['deleted_at'], ['name' => 'idx_users_deleted_at'])
            ->update();
    }
}
