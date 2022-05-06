<?php

namespace vaersaagod\linkmate\models;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Html;
use craft\validators\UrlValidator;
use Throwable;
use vaersaagod\linkmate\fields\LinkField;
use yii\base\Model;
use yii\validators\EmailValidator;

/**
 * Class InputLinkType
 *
 * @package vaersaagod\linkmate\models
 *
 * @property-read false[] $defaultSettings
 */
class InputLinkType extends Model implements LinkTypeInterface
{
    /**
     * @var string
     */
    public string $displayName;

    /**
     * @var string
     */
    public string $displayGroup = 'Common';

    /**
     * @var string
     */
    public string $inputType;

    /**
     * @var string
     */
    public string $placeholder;


    /**
     * ElementLinkType constructor.
     *
     * @param string|array $displayName
     * @param array        $options
     */
    public function __construct($displayName, array $options = [])
    {
        if (is_array($displayName)) {
            $options = $displayName;
        } else {
            $options['displayName'] = $displayName;
        }

        parent::__construct($options);
    }

    /**
     * @return array
     */
    public function getDefaultSettings(): array
    {
        return [
            'allowAliases' => false,
            'disableValidation' => false,
        ];
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return Craft::t('linkmate', $this->displayName);
    }

    /**
     * @return string
     */
    public function getDisplayGroup(): string
    {
        return Craft::t('linkmate', $this->displayGroup);
    }

    /**
     * @inheritdoc
     */
    public function getElement(Link $link, bool $ignoreStatus = false): ?ElementInterface
    {
        return null;
    }

    /**
     * @param string                $linkTypeName
     * @param LinkField             $field
     * @param Link|string           $value
     * @param ElementInterface|null $element
     *
     * @return string
     */
    public function getInputHtml(string $linkTypeName, LinkField $field, Link|string $value, ElementInterface $element = null): string
    {
        $settings = $field->getLinkTypeSettings($linkTypeName, $this);
        $isSelected = $value->type === $linkTypeName;
        $value = $isSelected ? $value->value : '';

        $textFieldOptions = [
            'disabled' => $field->isStatic(),
            'id' => $field->handle.'-'.$linkTypeName,
            'name' => $field->handle.'['.$linkTypeName.']',
            'value' => $value,
        ];

        if (isset($this->inputType) && !$settings['disableValidation']) {
            $textFieldOptions['type'] = $this->inputType;
        }

        if (isset($this->placeholder)) {
            $textFieldOptions['placeholder'] = Craft::t('linkmate', $this->placeholder);
        }

        try {
            return Craft::$app->view->renderTemplate('linkmate/_input-input', [
                'isSelected' => $isSelected,
                'linkTypeName' => $linkTypeName,
                'textFieldOptions' => $textFieldOptions,
            ]);
        } catch (Throwable $throwable) {
            $message = Craft::t(
                'linkmate',
                'Error: Could not render the template for the field `{name}`.',
                ['name' => $this->getDisplayName()]
            );
            Craft::error($message . ' ' . $throwable->getMessage());

            return Html::tag('p', $message);
        }
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function getLinkValue(mixed $value): mixed
    {
        return is_string($value) ? $value : '';
    }

    /**
     * @param Link $link
     *
     * @return null|string
     */
    public function getRawUrl(Link $link): ?string
    {
        if ($this->isEmpty($link)) {
            return null;
        }

        $url = $link->value;
        $field = $link->getLinkField();

        if (!is_null($field)) {
            $settings = $field->getLinkTypeSettings($link->type, $this);
            if ($settings['allowAliases']) {
                $url = Craft::getAlias($url);
            }
        }

        return $url;
    }

    /**
     * @param string    $linkTypeName
     * @param LinkField $field
     *
     * @return string
     */
    public function getSettingsHtml(string $linkTypeName, LinkField $field): string
    {
        try {
            return Craft::$app->view->renderTemplate('linkmate/_settings-input', [
                'settings' => $field->getLinkTypeSettings($linkTypeName, $this),
                'elementName' => $this->getDisplayName(),
                'linkTypeName' => $linkTypeName,
            ]);
        } catch (Throwable $throwable) {
            $message = Craft::t(
                'linkmate',
                'Error: Could not render the template for the field `{name}`.',
                ['name' => $this->getDisplayName()]
            );
            Craft::error($message . ' ' . $throwable->getMessage());

            return Html::tag('p', $message);
        }
    }

    /**
     * @param Link $link
     *
     * @return null|string
     */
    public function getText(Link $link): ?string
    {
        return null;
    }

    /**
     * @param Link $link
     *
     * @return null|string
     */
    public function getUrl(Link $link): ?string
    {
        $url = $this->getRawUrl($link);
        if (is_null($url)) {
            return null;
        }

        return match ($this->inputType) {
            'email' => 'mailto:'.$url,
            'tel' => 'tel:'.$url,
            default => $url,
        };
    }

    /**
     * @inheritdoc
     */
    public function hasElement(Link $link, bool $ignoreStatus = false): bool
    {
        return false;
    }

    /**
     * @param Link $link
     *
     * @return bool
     */
    public function isEmpty(Link $link): bool
    {
        if (is_string($link->value)) {
            return trim($link->value) === '';
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function validateSettings(array $settings): array
    {
        return $settings;
    }

    /**
     * @param LinkField $field
     * @param Link      $link
     *
     * @return array|null
     */
    public function validateValue(LinkField $field, Link $link): ?array
    {
        $value = $this->getRawUrl($link);
        if (is_null($value)) {
            return null;
        }

        $settings = $field->getLinkTypeSettings($link->type, $this);
        if ($settings['disableValidation']) {
            return null;
        }

        $enableIDN = defined('INTL_IDNA_VARIANT_UTS46');

        switch ($this->inputType) {
            case('email'):
                (new EmailValidator(['enableIDN' => $enableIDN]))->validate($value, $error);
                if (!is_null($error)) {
                    return [$error, []];
                }
                break;

            case('tel'):
                $regexp = '/^[0-9+\(\)#\.\s\/ext-]+$/';
                if (!filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => $regexp]])) {
                    return [Craft::t('linkmate', 'Please enter a valid phone number.'), []];
                }
                break;

            case('url'):
                (new UrlValidator(['enableIDN' => $enableIDN]))->validate($value, $error);
                if (!is_null($error)) {
                    return [$error, []];
                }
                break;
        }

        return null;
    }
}
