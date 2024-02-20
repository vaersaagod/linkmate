# LinkMate plugin for Craft CMS

_Let's hook you up, mate!_

![Screenshot](resources/img/plugin-icon.png)

This is a link field for Craft CMS, forked from Sebastian Lenz' fabolous 
[Typed Link Field](https://github.com/sebastian-lenz/craft-linkfield) (v1). 
It's made for Værsågod & friends - no support is given whatsoever. You 
should probably use the original instead.

## Requirements

This plugin requires Craft CMS 5.0.0 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require vaersaagod/linkmate

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for LinkMate.

## Templating

Link fields on your models will return an instance of `vaersaagod\linkmate\models\Link`. Rendering a link
field directly within a template will return the url the field is pointing to.

```
<a href="{{ item.myLinkField }}">Link</a>
```

You can use the following accessors to get the different properties of the link:

```
{{ item.myLinkField.getElement() }}
{{ item.myLinkField.getLinkAttributes() }}
{{ item.myLinkField.getTarget() }}
{{ item.myLinkField.getText() }}
{{ item.myLinkField.getUrl() }}
{{ item.myLinkField.hasElement() }}
{{ item.myLinkField.isEmpty() }}
{{ item.myLinkField.getCustomText() }}
```

Use the `getLink` utility function to render a full html link:

```
{{ item.myLinkField.getLink() }}
```

You can pass the desired content of the link as a string, e.g.
```
{{ entry.linkField.getLink('Imprint') }}
```

You may also pass an array of attributes. When doing this you can override
the default attributes `href` and `target`. The special attribute `text`
will be used as the link content.
```
{{ entry.linkField.getLink({
  class: 'my-link-class',
  target: '_blank',
  text: 'Imprint',
}) }}
```

You can also compose your own markup quickly by simply consuming the link
attributes exposed by `getLinkAttributes` on the link model. This method accepts
an additional parameter allowing you to inject additional attributes as an
associative array.
```
<a {{ entry.linkField.getLinkAttributes() }}>
  <span>Custom markup</span>
</a>
```

## API

You can register additional link types by listening to the `EVENT_REGISTER_LINK_TYPES` 
event of the plugin. If you just want to add another element type, you can do it like this in
your module:

```php
use craft\commerce\elements\Product;
use vaersaagod\linkmate\LinkMate as LinkPlugin;
use vaersaagod\linkmate\events\LinkTypeEvent;
use vaersaagod\linkmate\models\ElementLinkType;
use yii\base\Event;

/**
 * Custom module class.
 */
class Module extends \yii\base\Module
{
  public function init() {
    parent::init();
    Event::on(
      LinkPlugin::class,
      LinkPlugin::EVENT_REGISTER_LINK_TYPES,
      function(LinkTypeEvent $event) {
        $event->linkTypes['product'] = new ElementLinkType(Product::class);
      }
    );
  }
}
```

Each link type must have an unique name and a definition object implementing `vaersaagod\linkmate\modles\LinkTypeInterface`. 
Take a look at the bundled link types `ElementLinkType` and `InputLinkType` to get an idea of how to write your own 
link type definitions.
