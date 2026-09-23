<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUnitsMigration extends AbstractMigration
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
        $table = $this->table('units', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('code', 'string', ['limit' => 20])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('international_code', 'string', ['limit' => 10, 'null' => true])
            ->addTimestamps()
            ->addIndex(['code'], ['unique' => true, 'name' => 'uq_units_code'])
            ->create();
    }
}
