<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\titleentriesfield\migrations;

use craft\db\Migration;
use craft\mail\transportadapters\Php;

class Install extends Migration
{
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%relations}}', 'linkTitle')) {
            $this->addColumn('{{%relations}}', 'linkTitle', 'string');
        }


        echo " done\n";
    }

    public function safeDown()
    {
        if ($this->db->columnExists('{{%relations}}', 'linkTitle')) {
            $this->dropColumn('{{%relations}}', 'linkTitle');

        }
        return true;
    }

}
