<?php

namespace vaersaagod\linkmate\validators;

use vaersaagod\linkmate\fields\LinkField;
use vaersaagod\linkmate\models\Link;
use yii\validators\Validator;

/**
 * Class LinkFieldValidator
 * @package vaersaagod\linkmate
 */
class LinkFieldValidator extends Validator
{
  /**
   * @var LinkField
   */
  public $field;

  /**
   * @param mixed $value
   * @return array|null
   */
  protected function validateValue($value) {
    if ($value instanceof Link) {
      $linkType = $value->getLinkType();

      if (!is_null($linkType)) {
        return $linkType->validateValue($this->field, $value);
      }
    }

    return null;
  }
}
