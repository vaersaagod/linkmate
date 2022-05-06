<?php

namespace vaersaagod\linkmate\events;

use vaersaagod\linkmate\models\LinkTypeInterface;
use yii\base\Event;

/**
 * LinkTypeEvent class.
 */
class LinkTypeEvent extends Event
{
    /**
     * @var LinkTypeInterface[]
     */
    public array $linkTypes;
}
