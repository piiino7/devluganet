<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class OrderItemsMigration extends AbstractMigration
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
        $table = $this->table('order_items', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('order_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('product_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('product_name', 'string', ['limit' => 500, 'null' => false])
            ->addColumn('product_article', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('product_code', 'string', ['limit' => 50, 'null' => true])
            ->addColumn('price', 'decimal', ['precision' => 15, 'scale' => 2, 'default' => 0, 'null' => false])
            ->addColumn('quantity', 'decimal', ['precision' => 15, 'scale' => 3, 'default' => 0, 'null' => false])
            ->addColumn('unit_name', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('unit_code', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('tax_rate', 'decimal', ['precision' => 5, 'scale' => 2, 'null' => true])
            ->addColumn('total', 'decimal', ['precision' => 15, 'scale' => 2, 'default' => 0, 'null' => false])
            ->addTimestamps()
            ->addIndex(['order_id'], ['name' => 'idx_oi_order'])
            ->addIndex(['product_id'], ['name' => 'idx_oi_product'])
            ->addForeignKey('order_id', 'orders', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_oi_order',
            ])
            ->addForeignKey('product_id', 'products', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'RESTRICT',
                'constraint' => 'fk_oi_product',
            ])
            ->create();
    }
}
