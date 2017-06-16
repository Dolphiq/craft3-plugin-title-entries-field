<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\linkfield\migrations;

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
        if (!$this->db->columnExists('{{%relations}}', 'linkFieldLabel')) {
            $this->addColumn('{{%relations}}', 'linkFieldLabel', 'string');
        }


        echo " done\n";
    }

    public function safeDown()
    {
        if ($this->db->columnExists('{{%relations}}', 'linkFieldLabel')) {
          $this->dropColumn('{{%relations}}', 'linkFieldLabel');

        }
        return true;
    }

}
