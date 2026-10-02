<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CartMigration extends AbstractMigration
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
        $table = $this->table('carts', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('seller_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('product_id', 'biginteger', ['signed' => false, 'null' => false])
            //->addColumn('client_external_id', 'string', ['limit' => 50])
            ->addColumn('quantity', 'decimal', ['precision' => 15, 'scale' => 3, 'default' => 1])
            ->addTimestamps()
            ->addIndex(['seller_id'], ['name' => 'idx_cart_seller'])
            ->addIndex(['product_id'], ['name' => 'idx_cart_product'])
            //->addIndex(['client_external_id'], ['name' => 'idx_cart_client'])
            ->addForeignKey('seller_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_carts_seller',
            ])
            ->addForeignKey('product_id', 'products', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
                'constraint' => 'fk_carts_product',
            ])
            ->create();
    }
}
