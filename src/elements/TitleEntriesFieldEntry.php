<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\titleentriesfield\elements;

use Craft;
use craft\fields\BaseRelationField;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\base\Field;


class TitleEntriesFieldEntry extends Entry {

  public $linkTitle;

  public function getLinkFieldLabel () {
    return $this->linkTitle;
  }

  public function getTitle () {
    return ($this->linkTitle != ''?$this->linkTitle:$this->title);
  }
}