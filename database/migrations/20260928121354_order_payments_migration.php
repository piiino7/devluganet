<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class OrderPaymentsMigration extends AbstractMigration
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
        $table = $this->table('order_payments', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('order_id', 'biginteger', ['signed' => false, 'null' => false])
            ->addColumn('provider', 'string', ['limit' => 30, 'null' => false])
            ->addColumn('qr_id', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('amount', 'decimal', ['precision' => 15, 'scale' => 2, 'default' => 0, 'null' => false])
            ->addColumn('currency', 'string', ['limit' => 10, 'null' => false, 'default' => 'руб.'])
            ->addColumn('status', 'string', ['limit' => 30, 'null' => false])
            ->addColumn('payload', 'json', ['null' => true])
            ->addColumn('paid_at', 'datetime', ['null' => true])
            ->addTimestamps()
            ->addIndex(['order_id'], ['name' => 'idx_op_order'])
            ->addIndex(['qr_id'], ['name' => 'idx_op_qr'])
            ->addForeignKey('order_id', 'orders', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_op_order',
            ])
            ->create();
    }
}
