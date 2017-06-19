<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\titleentriesfield\migrations;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;
use craft\elements\User;
use craft\helpers\StringHelper;
use craft\mail\Mailer;
use craft\mail\transportadapters\Php;
use craft\models\Info;
use craft\models\Site;

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
