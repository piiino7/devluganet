<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProductGroupsProductMigration extends AbstractMigration
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
        $table = $this->table('product_groups_products', [
            'id' => false,
            'primary_key' => ['product_id', 'group_id']
        ]);
        $table->addColumn('product_id', 'biginteger', ['signed' => false])
            ->addColumn('group_id', 'biginteger', ['signed' => false])
            ->addTimestamps()
            ->addIndex(['group_id'], ['name' => 'idx_pgl_group'])
            ->addForeignKey('product_id', 'products', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_pgl_product',
            ])
            ->addForeignKey('group_id', 'product_groups', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_pgl_group',
            ])
            ->create();
    }
}
