<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProductGroupsMigration extends AbstractMigration
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
        $table = $this->table('product_groups', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('external_id', 'string', ['limit' => 36, 'null' => false])
            ->addColumn('parent_id', 'biginteger', ['signed' => false])
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addTimestamps()
            ->addIndex(['external_id'], [
                'unique' => true,
                'name'   => 'uq_product_groups_external_id',
            ])
            ->addIndex(['parent_id'], ['name' => 'idx_product_groups_parent'])
            ->addForeignKey('parent_id', 'product_groups', 'id', [
                'delete'     => 'SET_NULL',
                'update'     => 'CASCADE',
                'constraint' => 'fk_product_groups_parent',
            ])
            ->create();
    }
}
