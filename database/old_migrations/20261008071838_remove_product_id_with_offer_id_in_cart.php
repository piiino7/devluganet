<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveProductIdWithOfferIdInCart extends AbstractMigration
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
        $this->table('carts')
            ->dropForeignKey('product_id')
            ->removeIndexByName('idx_cart_product')
            ->removeColumn('product_id')
            ->update();

        $this->table('carts')
            ->addColumn('offer_id', 'biginteger', ['signed' => false, 'null' => false, 'after' => 'seller_id'])
            ->addIndex(['offer_id'], ['name' => 'idx_cart_offer'])
            ->addForeignKey('offer_id', 'offers', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
                'constraint' => 'fk_carts_offer',
            ])
            ->update();
    }
}
