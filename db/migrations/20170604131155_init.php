<?php

use Phinx\Migration\AbstractMigration;

class Init extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $this->table('keys')
            ->addColumn('service', 'string', ['length' => 20])
            ->addColumn('type', 'string', ['length' => 20, 'default' => 'totp'])
            ->addColumn('label', 'string', ['length' => 255])
            ->addColumn('secret', 'string', ['length' => 255])
            ->addColumn('original', 'string', ['length' => 255, 'null' => true])
            ->addColumn('period', 'integer', ['default' => 30])
            ->addColumn('telegram_id', 'integer')
            ->addTimestamps()
            ->addIndex(['telegram_id', 'secret'], ['unique' => true])
            ->save();
    }
}
