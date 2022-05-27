<?php

namespace vaersaagod\linkmate\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Json;
use Exception;
use Throwable;
use vaersaagod\linkmate\helpers\MigrateHelper;
use vaersaagod\linkmate\LinkMate;
use vaersaagod\linkmate\models\Link;
use vaersaagod\linkmate\models\LinkTypeInterface;
use vaersaagod\linkmate\validators\LinkFieldValidator;
use yii\db\Schema;

/**
 * Class LinkField
 *
 * @package vaersaagod\linkmate\fields
 *
 * @property-read array[]             $elementValidationRules
 * @property-read string              $contentColumnType
 * @property-read LinkTypeInterface[] $allowedLinkTypes
 * @property-read string              $settingsHtml
 */
class LinkField extends Field
{

    /**
     * @var string
     */
    public const UI_MODE_NORMAL = 'normal';

    /**
     * @var string
     */
    public const UI_MODE_COMPACT = 'compact';

    /**
     * @var bool
     */
    public bool $allowCustomText = true;

    /**
     * @var string|array
     */
    public string|array $allowedLinkNames = '*';

    /**
     * @var bool
     */
    public bool $allowTarget = false;

    /**
     * @var bool
     */
    public bool $autoNoReferrer = false;

    /**
     * @var string
     */
    public string $defaultLinkName = 'entry';

    /**
     * @var string
     */
    public string $defaultText = '';

    /**
     * @var bool
     */
    public bool $enableAriaLabel = false;

    /**
     * @var bool
     */
    public bool $enableTitle = false;

    /**
     * @var array
     */
    public array $typeSettings = [];

    /**
     * @var string
     */
    public string $uiMode = self::UI_MODE_NORMAL;

    /**
     * @var bool
     */
    private bool $isStatic = false;


    /**
     * @param bool $isNew
     *
     * @return bool
     */
    public function beforeSave(bool $isNew): bool
    {
        if (is_array($this->allowedLinkNames)) {
            $this->allowedLinkNames = array_filter($this->allowedLinkNames);
            foreach ($this->allowedLinkNames as $linkName) {
                if ($linkName === '*') {
                    $this->allowedLinkNames = '*';
                    break;
                }
            }
        } else {
            $this->allowedLinkNames = '*';
        }

        return parent::beforeSave($isNew);
    }

    /**
     * Get Content Column Type
     * Used to set the correct column type in the DB
     *
     * @return string
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_TEXT;
    }

    /**
     * @param mixed                 $value
     * @param ElementInterface|null $element
     *
     * @return mixed
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        if ($value instanceof Link) {
            return $value;
        }

        $attr = [
            'linkField' => $this,
            'owner' => $element,
        ];

        if (is_string($value)) {
            // If value is a string we are loading the data from the database
            try {
                $decodedValue = Json::decode($value, true);
                if (is_array($decodedValue)) {
                    $attr += $decodedValue;
                }
            } catch (Exception) {
            }
        } else if (is_array($value) && isset($value['isCpFormData'])) {
            // If it is an array and the field `isCpFormData` is set, we are saving a cp form
            $attr += [
                'ariaLabel' => $this->enableAriaLabel && isset($value['ariaLabel']) ? $value['ariaLabel'] : null,
                'customQuery' => isset($value['customQuery']) ? $value['customQuery'] : null,
                'customText' => $this->allowCustomText && isset($value['customText']) ? $value['customText'] : null,
                'target' => $this->allowTarget && isset($value['target']) ? $value['target'] : null,
                'title' => $this->enableTitle && isset($value['title']) ? $value['title'] : null,
                'type' => isset($value['type']) ? $value['type'] : null,
                'value' => $this->getLinkValue($value)
            ];
        } else if (is_array($value)) {
            // Finally, if it is an array it is a serialized value
            $attr += $value;
        }

        $attr = MigrateHelper::sanitize($attr);

        if (isset($attr['type']) && !$this->isAllowedLinkType($attr['type'])) {
            $attr['type'] = null;
            $attr['value'] = null;
        }

        return new Link(array_filter(
            $attr,
            static function($key) {
                return in_array($key, [
                    'ariaLabel',
                    'customQuery',
                    'customText',
                    'linkField',
                    'owner',
                    'target',
                    'title',
                    'type',
                    'value'
                ]);
            },
            ARRAY_FILTER_USE_KEY
        ));
    }

    /**
     * @return LinkTypeInterface[]
     */
    public function getAllowedLinkTypes(): array
    {
        $allowedLinkNames = $this->allowedLinkNames;
        $linkTypes = LinkMate::getInstance()->getLinkTypes();

        if (is_string($allowedLinkNames)) {
            if ($allowedLinkNames === '*') {
                return $linkTypes;
            }

            $allowedLinkNames = [$allowedLinkNames];
        }

        return array_filter($linkTypes,
            static function($linkTypeName) use ($allowedLinkNames) {
                return in_array($linkTypeName, $allowedLinkNames, true);
            },
            ARRAY_FILTER_USE_KEY);
    }

    /**
     * @return array
     */
    public function getElementValidationRules(): array
    {
        return [
            [LinkFieldValidator::class, 'field' => $this],
        ];
    }

    /**
     * @param Link                  $value
     * @param ElementInterface|null $element
     *
     * @return string
     * @throws Throwable
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $linkTypes = $this->getAllowedLinkTypes();
        $linkNames = [];
        $linkInputs = [];
        $singleType = count($linkTypes) === 1 ? array_keys($linkTypes)[0] : null;

        if (!array_key_exists($value->type, $linkTypes) && count($linkTypes) > 0) {
            $value->type = array_keys($linkTypes)[0];
            $value->value = null;
        }

        if (
            !empty($this->defaultLinkName) &&
            array_key_exists($this->defaultLinkName, $linkTypes) &&
            $value->isEmpty()
        ) {
            $value->type = $this->defaultLinkName;
        }

        foreach ($linkTypes as $linkTypeName => $linkType) {
            $linkNames[$linkTypeName] = $linkType->getDisplayName();
            $linkInputs[] = $linkType->getInputHtml($linkTypeName, $this, $value, $element);
        }

        asort($linkNames);

        return Craft::$app->getView()->renderTemplate('linkmate/_input', [
            'hasSettings' => $this->hasSettings(),
            'isStatic' => $this->isStatic,
            'linkInputs' => implode('', $linkInputs),
            'linkNames' => $linkNames,
            'name' => $this->handle,
            'nameNs' => Craft::$app->view->namespaceInputId($this->handle),
            'settings' => $this->getSettings(),
            'singleType' => $singleType,
            'value' => $value,
        ]);
    }

    /**
     * @param string            $linkTypeName
     * @param LinkTypeInterface $linkType
     *
     * @return array
     */
    public function getLinkTypeSettings(string $linkTypeName, LinkTypeInterface $linkType): array
    {
        $linkTypeName = strtolower($linkTypeName);
        $settings = $linkType->getDefaultSettings();
        if (array_key_exists($linkTypeName, $this->typeSettings)) {
            $settings = $linkType->validateSettings(
                $this->typeSettings[$linkTypeName] + $settings
            );
        }

        return $settings;
    }

    /**
     * @return string
     * @throws Throwable
     */
    public function getSettingsHtml(): string
    {
        $settings = $this->getSettings();
        $allowedLinkNames = $settings['allowedLinkNames'];
        $linkTypes = [];
        $linkNames = [];

        $allTypesAllowed = false;
        if (!is_array($allowedLinkNames)) {
            $allTypesAllowed = $allowedLinkNames === '*';
        } else {
            foreach ($allowedLinkNames as $linkName) {
                if ($linkName === '*') {
                    $allTypesAllowed = true;
                    break;
                }
            }
        }

        foreach (LinkMate::getInstance()?->getLinkTypes() as $linkTypeName => $linkType) {
            $linkTypes[] = [
                'displayName' => $linkType->getDisplayName(),
                'enabled' => $allTypesAllowed || (is_array($allowedLinkNames) && in_array($linkTypeName, $allowedLinkNames, true)),
                'name' => $linkTypeName,
                'group' => $linkType->getDisplayGroup(),
                'settings' => $linkType->getSettingsHtml($linkTypeName, $this),
            ];

            $linkNames[$linkTypeName] = $linkType->getDisplayName();
        }

        asort($linkNames);
        usort($linkTypes, static function($a, $b) {
            return $a['group'] === $b['group']
                ? strcmp($a['displayName'], $b['displayName'])
                : strcmp($a['group'], $b['group']);
        });

        return Craft::$app->getView()->renderTemplate('linkmate/_settings', [
            'allTypesAllowed' => $allTypesAllowed,
            'name' => 'linkField',
            'nameNs' => Craft::$app->view->namespaceInputId('linkField'),
            'linkTypes' => $linkTypes,
            'linkNames' => $linkNames,
            'settings' => $settings,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml($value, ElementInterface $element): string
    {
        $this->isStatic = true;
        $result = parent::getStaticHtml($value, $element);
        $this->isStatic = false;

        return $result;
    }

    /**
     * @return boolean
     */
    public function hasSettings(): bool
    {
        return (
            $this->allowCustomText ||
            $this->enableAriaLabel ||
            $this->enableTitle
        );
    }

    /**
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    /**
     * @param                  $value
     * @param ElementInterface $element
     *
     * @return bool
     */
    public function isValueEmpty($value, ElementInterface $element): bool
    {
        if ($value instanceof Link) {
            return $value->isEmpty();
        }

        return true;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    private function isAllowedLinkType(string $type): bool
    {
        $allowedLinkTypes = $this->getAllowedLinkTypes();

        return array_key_exists($type, $allowedLinkTypes);
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    private function getLinkValue(array $data): mixed
    {
        $linkTypes = LinkMate::getInstance()?->getLinkTypes();
        $type = $data['type'];
        if (!array_key_exists($type, $linkTypes)) {
            return null;
        }

        return $linkTypes[$type]->getLinkValue($data[$type]);
    }

    /**
     * @return string
     */
    public static function displayName(): string
    {
        return Craft::t('linkmate', 'LinkMate field');
    }
}
