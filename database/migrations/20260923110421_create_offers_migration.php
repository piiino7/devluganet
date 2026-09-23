<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOffersMigration extends AbstractMigration
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
        $table = $this->table('offers', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('external_id', 'string', ['limit' => 50])
            ->addColumn('product_id', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('package_id', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('quantity', 'decimal', ['precision' => 15, 'scale' => 3, 'default' => 0])
            ->addTimestamps()
            ->addIndex(['external_id'], ['unique' => true, 'name' => 'uq_offers_external_id'])
            ->addIndex(['product_id'], ['name' => 'idx_offers_product'])
            ->addIndex(['package_id'], ['name' => 'idx_offers_package'])
            ->addForeignKey('product_id', 'products', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_offers_product',
            ])
            ->addForeignKey('package_id', 'offer_packages', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_offers_package',
            ])
            ->create();
    }
}
