<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RefreshToken extends AbstractMigration
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
        $this->table('refresh_tokens', [
            'id'          => false,
            'primary_key' => ['id'],
            'collation'   => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('id', 'biginteger', ['identity' => true, 'signed' => false])
            ->addColumn('user_id', 'biginteger', ['signed' => false])
            ->addColumn('token_hash', 'string', ['limit' => 64])
            ->addColumn('expires_at', 'datetime')
            ->addColumn('revoked_at', 'datetime', ['null' => true])
            ->addColumn('replaced_by', 'biginteger', ['signed' => false, 'null' => true])
            ->addColumn('user_agent', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('ip', 'string', ['limit' => 45, 'null' => true])
            ->addTimestamps()
            ->addIndex(['token_hash'], ['unique' => true, 'name' => 'uq_refresh_tokens_hash'])
            ->addIndex(['user_id'], ['name' => 'idx_refresh_tokens_user'])
            ->addIndex(['expires_at'], ['name' => 'idx_refresh_tokens_expires'])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
                'constraint' => 'fk_refresh_tokens_user',
            ])
            ->create();
    }
}
