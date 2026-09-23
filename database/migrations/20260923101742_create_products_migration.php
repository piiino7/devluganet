<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProductsMigration extends AbstractMigration
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
        $table = $this->table('products', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('external_id', 'string', ['limit' => 50])
            ->addColumn('code', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('article', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('name', 'string', ['limit' => 500])
            ->addColumn('full_name', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('kind', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('nomenclature_type', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('unit_id', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('tax_rate_id', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('is_active', 'boolean', ['default' => true])
            ->addTimestamps()
            ->addIndex(['external_id'], [
                'unique' => true,
                'name'   => 'uq_products_external_id',
            ])
            ->addIndex(['code'], ['name' => 'idx_products_code'])
            ->addIndex(['kind'], ['name' => 'idx_products_kind'])
            ->addIndex(['unit_id'], ['name' => 'idx_products_unit'])
            ->addIndex(['tax_rate_id'], ['name' => 'idx_products_tax'])
            ->addForeignKey('unit_id', 'units', 'id', [
                'delete'     => 'SET_NULL',
                'update'     => 'CASCADE',
                'constraint' => 'fk_products_unit',
            ])
            ->addForeignKey('tax_rate_id', 'tax_rates', 'id', [
                'delete'     => 'SET_NULL',
                'update'     => 'CASCADE',
                'constraint' => 'fk_products_tax',
            ])
            ->create();
    }
}
