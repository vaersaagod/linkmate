<?php

namespace vaersaagod\linkmate;

use craft\base\Plugin;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;

use vaersaagod\linkmate\events\LinkTypeEvent;
use vaersaagod\linkmate\fields\LinkField;
use vaersaagod\linkmate\models\ElementLinkType;
use vaersaagod\linkmate\models\InputLinkType;
use vaersaagod\linkmate\models\SiteLinkType;
use vaersaagod\linkmate\models\LinkTypeInterface;

use yii\base\Event;

/**
 * Class LinkMate
 * @package linkmate
 */
class LinkMate extends Plugin
{
    /**
     * @var LinkTypeInterface[]
     */
    private array $linkTypes;

    /** @var string */
    public string $schemaVersion = '2.0.0';

    /**
     * @event events\LinkTypeEvent
     */
    public const EVENT_REGISTER_LINK_TYPES = 'registerLinkTypes';


    /**
     * @return void
     */
    public function init(): void
    {
        parent::init();

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            [$this, 'onRegisterFieldTypes']
        );
    }

    /**
     * @return LinkTypeInterface[]
     */
    public function getLinkTypes(): array
    {
        if (!isset($this->linkTypes)) {
            $event = new LinkTypeEvent();
            $event->linkTypes = $this->createDefaultLinkTypes();
            $this->trigger(self::EVENT_REGISTER_LINK_TYPES, $event);

            $this->linkTypes = $event->linkTypes;
        }

        return $this->linkTypes;
    }

    /**
     * @return LinkTypeInterface[]
     */
    private function createDefaultLinkTypes(): array
    {
        $result = [
            'url' => new InputLinkType([
                'displayName' => 'URL',
                'displayGroup' => 'Input fields',
                'inputType' => 'url'
            ]),
            'custom' => new InputLinkType([
                'displayName' => 'Custom',
                'displayGroup' => 'Input fields',
                'inputType' => 'text'
            ]),
            'email' => new InputLinkType([
                'displayName' => 'Mail',
                'displayGroup' => 'Input fields',
                'inputType' => 'email'
            ]),
            'tel' => new InputLinkType([
                'displayName' => 'Telephone',
                'displayGroup' => 'Input fields',
                'inputType' => 'tel'
            ]),
            'asset' => new ElementLinkType([
                'displayGroup' => 'Craft CMS',
                'elementType' => Asset::class,
            ]),
            'category' => new ElementLinkType([
                'displayGroup' => 'Craft CMS',
                'elementType' => Category::class,
            ]),
            'entry' => new ElementLinkType([
                'displayGroup' => 'Craft CMS',
                'elementType' => Entry::class,
            ]),
            'user' => new ElementLinkType([
                'displayGroup' => 'Craft CMS',
                'elementType' => User::class,
            ]),
            'site' => new SiteLinkType([
                'displayGroup' => 'Craft CMS',
                'displayName' => 'Site',
            ]),
        ];

        // Add Craft Commerce elements if commerce is installed
        $commerce = \Craft::$app->plugins->getPlugin('commerce');

        if ($commerce instanceof  \craft\commerce\elements\Product) {
            $result['craftCommerce-product'] = new ElementLinkType([
                'displayGroup' => 'Craft Commerce',
                'elementType' => \craft\commerce\elements\Product::class
            ]);
        }

        return $result;
    }

    /**
     * @param RegisterComponentTypesEvent $event
     */
    public function onRegisterFieldTypes(RegisterComponentTypesEvent $event): void
    {
        $event->types[] = LinkField::class;
    }
}
