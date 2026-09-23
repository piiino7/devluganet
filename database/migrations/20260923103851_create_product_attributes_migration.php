<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProductAttributesMigration extends AbstractMigration
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
        $table = $this->table('product_attributes', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('product_id', 'biginteger', ['signed' => false])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('value', 'text', ['null' => true])
            ->addTimestamps()
            ->addIndex(['product_id'], ['name' => 'idx_pa_product'])
            ->addIndex(['name'], ['name' => 'idx_pa_name'])
            ->addForeignKey('product_id', 'products', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_pa_product',
            ])
            ->create();
    }
}
