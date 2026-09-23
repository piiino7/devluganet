<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRoleMigration extends AbstractMigration
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
        $table = $this->table('roles', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('description', 'string', ['limit' => 255])
            ->addTimestamps()
            ->addIndex(['name'], ['unique' => true])
            ->create();
    }
}
