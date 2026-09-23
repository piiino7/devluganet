<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePriceTypesMigration extends AbstractMigration
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
        $table = $this->table('price_types', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('external_id', 'string', ['limit' => 50])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('currency', 'string', ['limit' => 10, 'default' => 'руб'])
            ->addColumn('tax_included', 'boolean', ['default' => false])
            ->addTimestamps()
            ->addIndex(['external_id'], ['unique' => true, 'name' => 'uq_price_types_external_id'])
            ->create();
    }
}
