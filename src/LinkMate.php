<?php

namespace vaersaagod\linkmate;

use Craft;
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
    private $linkTypes;

    /**
     * @event events\LinkTypeEvent
     */
    const EVENT_REGISTER_LINK_TYPES = 'registerLinkTypes';


    /**
     * @return void
     */
    public function init()
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
    public function getLinkTypes()
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
    private function createDefaultLinkTypes()
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
                'elementType' => Category::class
            ]),
            'entry' => new ElementLinkType([
                'displayGroup' => 'Craft CMS',
                'elementType' => Entry::class
            ]),
            'user' => new ElementLinkType([
                'displayGroup' => 'Craft CMS',
                'elementType' => User::class
            ]),
            'site' => new SiteLinkType([
                'displayGroup' => 'Craft CMS',
                'displayName' => 'Site',
            ]),
        ];

        // Add craft commerce elements
        if (class_exists('craft\commerce\elements\Product')) {
            $result['craftCommerce-product'] = new ElementLinkType([
                'displayGroup' => 'Craft commerce',
                'elementType' => 'craft\commerce\elements\Product'
            ]);
        }

        // Add solspace calendar elements
        if (class_exists('Solspace\Calendar\Elements\Event')) {
            $result['solspaceCalendar-event'] = new ElementLinkType([
                'displayGroup' => 'Solspace calendar',
                'elementType' => 'Solspace\Calendar\Elements\Event'
            ]);
        }

        return $result;
    }

    /**
     * @param RegisterComponentTypesEvent $event
     */
    public function onRegisterFieldTypes(RegisterComponentTypesEvent $event)
    {
        $event->types[] = LinkField::class;
    }
}
