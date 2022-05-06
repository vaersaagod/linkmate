<?php

namespace vaersaagod\linkmate\helpers;

use Craft;

/**
 * Migrate Helper
 *
 * @author    Værsågod
 * @package   vaersaagod\linkmate\helpers
 */
class MigrateHelper
{
    public const TYPE_MAP = [
        'fruitstudios\\linkit\\models\\Entry' => 'entry',
        'fruitstudios\\linkit\\models\\Category' => 'category',
        'fruitstudios\\linkit\\models\\Asset' => 'asset',
        'fruitstudios\\linkit\\models\\User' => 'user',
        'fruitstudios\\linkit\\models\\Url' => 'url',
        'fruitstudios\\linkit\\models\\Email' => 'email',
        'fruitstudios\\linkit\\models\\Phone' => 'tel',
    ];

    /**
     * @param array $values
     *
     * @return array
     */
    public static function sanitize(array $values): array
    {
        // If there isn't a type, something is wrong, and we can't do much more
        if (!isset($values['type'])) {
            return $values;
        }
        
        // Types in LinkIt for Craft 3 needs to be translated
        if (isset(self::TYPE_MAP[$values['type']])) {
            $values['type'] = self::TYPE_MAP[$values['type']];
        }
        
        // LinkIt for Craft 2 has the value stored in a separate prop named after element as an array 
        foreach (['entry', 'category', 'asset'] as $migrateField) {
            if ($values['type'] === $migrateField && isset($values[$migrateField]) && is_array($values[$migrateField])) {
                $values['value'] = $values[$migrateField][0];
                unset($values[$migrateField]);
            }
        }
        
        // Phone and emails in LinkIt fields for Craft 2 has the value stored in a separate prop named after element a string
        foreach (['tel', 'email'] as $migrateField) {
            if ($values['type'] === $migrateField && isset($values[$migrateField]) && !empty($values[$migrateField])) {
                $values['value'] = $values[$migrateField];
                unset($values[$migrateField]);
            }
        }
        
        // Custom url's in LinkIt for Craft 2 are special
        if (isset($values['type'], $values['custom']) && $values['type'] === 'custom') {
            $values['type'] = 'url';
            $values['value'] = $values['custom'];
            unset($values['custom']);
        }
        
        return $values;
    }
}
