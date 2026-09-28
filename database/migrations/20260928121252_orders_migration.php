<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class OrdersMigration extends AbstractMigration
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
        $table = $this->table('orders', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('external_id', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('number', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('seller_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('client_external_id', 'string', ['limit' => 50, 'null' => false])
            ->addColumn('client_name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('client_full_name', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('client_inn', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('client_phone', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('client_email', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('client_payload', 'json', ['null' => true])
            ->addColumn('status', 'string', ['limit' => 30, 'null' => false])
            ->addColumn('total', 'decimal', ['precision' => 15, 'scale' => 2, 'default' => 0, 'null' => false])
            ->addColumn('currency', 'string', ['limit' => 10, 'null' => false, 'default' => 'руб.'])
            ->addColumn('payment_provider', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('payment_qr_id', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('payment_qr_url', 'text', ['null' => true])
            ->addColumn('payment_qr_expires_at', 'datetime', ['null' => true])
            ->addColumn('payment_status', 'string', ['limit' => 30, 'null' => true])
            ->addColumn('payment_payload', 'json', ['null' => true])
            ->addColumn('paid_at', 'datetime', ['null' => true])
            ->addColumn('exported_at', 'datetime', ['null' => true])
            ->addColumn('comment', 'text', ['null' => true])
            ->addTimestamps()
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addIndex(['external_id'], ['unique' => true, 'name' => 'uq_orders_external'])
            ->addIndex(['number'], ['unique' => true, 'name' => 'uq_orders_number'])
            ->addIndex(['seller_id'], ['name' => 'idx_orders_seller'])
            ->addIndex(['status'], ['name' => 'idx_orders_status'])
            ->addIndex(['payment_qr_id'], ['name' => 'idx_orders_payment_qr'])
            ->addIndex(['exported_at'], ['name' => 'idx_orders_exported'])
            ->addForeignKey('seller_id', 'users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
                'constraint' => 'fk_orders_seller',
            ])
            ->create();
    }
}
