<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\linkfield\elements;

use Craft;
use craft\fields\BaseRelationField;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\base\Field;


class LinkFieldEntry extends Entry {

  public $linkFieldLabel;

  public function getLinkFieldLabel () {
    // var_dump($this);
    return $this->linkFieldLabel;
  }

}