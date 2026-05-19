<?php

/**
 * Create Pastie base tables (as of Nag 2.x).
 *
 * Copyright 2012-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Ralf Lang <ralf.lang@ralf-lang.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Pastie
 */
class PastieBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('pastie_pastes', $tableList)) {
            $t = $this->createTable('pastie_pastes', ['autoincrementKey' => 'paste_id']);
            $t->column('paste_uuid', 'string', ['limit' => 40, 'null' => false]);
            $t->column('paste_bin', 'string', ['limit' => 64, 'null' => false]);
            $t->column('paste_title', 'string', ['limit' => 255]);
            $t->column('paste_syntax', 'string', ['limit' => 16]);
            $t->column('paste_content', 'text');
            $t->column('paste_owner', 'string', ['limit' => 255]);
            $t->column('paste_timestamp', 'integer', ['null' => false]);
            $t->end();
            $this->addIndex('paste_uuid', ['paste_uuid']);
        }
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->dropTable('pastie_pastes');
    }
}
