<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\titleentriesfield\elements;

use craft\elements\Entry;


class TitleEntriesFieldEntry extends Entry
{

    public $linkTitle;

    public function getLinkFieldLabel()
    {
        return $this->linkTitle;
    }

    public function getTitle()
    {
        return ($this->linkTitle != '' ? $this->linkTitle : $this->title);
    }
}