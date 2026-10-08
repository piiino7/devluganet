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
            ->addColumn('offer_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('quantity', 'decimal', ['precision' => 15, 'scale' => 3, 'default' => 1])
            ->addColumn('service_date', 'date', ['null' => true])
            ->addTimestamps()
            ->addIndex(['seller_id'], ['name' => 'idx_cart_seller'])
            ->addIndex(['offer_id'], ['name' => 'idx_cart_offer'])
            ->addForeignKey('seller_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_carts_seller',
            ])
            ->addForeignKey('offer_id', 'offers', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
                'constraint' => 'fk_carts_offer',
            ])
            ->create();
    }
}
