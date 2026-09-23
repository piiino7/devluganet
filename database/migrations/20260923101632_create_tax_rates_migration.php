<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTaxRatesMigration extends AbstractMigration
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
        $table = $this->table('tax_rates', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('name', 'string', ['limit' => 50])
            ->addColumn('rate', 'decimal', ['precision' => 5, 'scale' => 2])
            ->addTimestamps()
            ->addIndex(['name', 'rate'], ['unique' => true, 'name' => 'uq_tax_rates_name_rate'])
            ->create();
    }
}
