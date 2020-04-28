<?php

namespace vaersaagod\linkmate\utilities;

use craft\base\ElementInterface;
use vaersaagod\linkmate\models\ElementLinkType;
use vaersaagod\linkmate\models\Link;

/**
 * Class ElementPrefetch
 */
class ElementPrefetch
{
  /**
   * @param string $handle
   * @param ElementInterface[] $sourceElements
   */
  static function prefetchElements($handle, array $sourceElements) {
    $elementTypes = array();

    /** @var ElementInterface $element */
    foreach ($sourceElements as $element) {
      $value = $element->$handle;

      if (!($value instanceof Link)) {
        continue;
      }

      $type = $value->getLinkType();
      if ($type instanceof ElementLinkType) {
        $elementTypes[(string)$type->elementType][$value->value][] = $value;
      }
    }

    foreach ($elementTypes as $type => $mappings) {
      $elements = $type::find()
        ->id(array_keys($mappings))
        ->all();

      $elementsById = array();
      foreach ($elements as $element) {
        $elementsById[$element->getId()] = $element;
      }

      foreach ($mappings as $id => $links) {
        if (!isset($elementsById[$id])) continue;
        $element = $elementsById[$id];

        foreach ($links as $link) {
          $link->setPrefetchedElement($element);
        }
      }
    }
  }
}
