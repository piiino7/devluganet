<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePricesMigration extends AbstractMigration
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
        $table = $this->table('prices', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('offer_id', 'biginteger', ['signed' => false])
            ->addColumn('price_type_id', 'biginteger', ['signed' => false])
            ->addColumn('price', 'decimal', ['precision' => 15, 'scale' => 2])
            ->addColumn('currency', 'string', ['limit' => 10, 'default' => 'руб'])
            ->addColumn('coefficient', 'decimal', ['precision' => 10, 'scale' => 4, 'default' => 1])
            ->addColumn('presentation', 'string', ['limit' => 255, 'null' => true])
            ->addTimestamps()
            ->addIndex(['offer_id', 'price_type_id'], ['unique' => true, 'name' => 'uq_prices_offer_type'])
            ->addIndex(['price_type_id'], ['name' => 'idx_prices_type'])
            ->addForeignKey('offer_id', 'offers', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_prices_offer',
            ])
            ->addForeignKey('price_type_id', 'price_types', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_prices_type',
            ])
            ->create();
    }
}
