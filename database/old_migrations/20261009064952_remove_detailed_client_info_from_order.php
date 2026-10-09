<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveDetailedClientInfoFromOrder extends AbstractMigration
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
        $this->table('orders')
            ->removeColumn('client_external_id')
            ->removeColumn('client_name')
            ->removeColumn('client_full_name')
            ->removeColumn('client_inn')
            ->removeColumn('client_phone')
            ->removeColumn('client_email')
            ->removeColumn('client_payload')
            ->update();

        $this->table('orders')
            ->addColumn('client_personal_account', 'string', ['limit' => 50, 'null' => false, 'after' => 'seller_id'])
            ->update();
    }
}
