<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateImportLogsMigration extends AbstractMigration
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
        $table = $this->table('import_logs', [
            'id' => false,
            'primary_key' => ['id']
        ]);
        $table->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('filename', 'string', ['limit' => 255])
            ->addColumn('file_hash', 'string', ['limit' => 64])
            ->addColumn('started_at', 'datetime')
            ->addColumn('finished_at', 'datetime', ['null' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'running'])
            ->addColumn('products_total', 'integer', ['default' => 0])
            ->addColumn('products_new', 'integer', ['default' => 0])
            ->addColumn('products_updated', 'integer', ['default' => 0])
            ->addColumn('error_message', 'text', ['null' => true])
            ->addIndex(['file_hash'], ['name' => 'idx_import_logs_hash'])
            ->addIndex(['status'], ['name' => 'idx_import_logs_status'])
            ->create();
    }
}
