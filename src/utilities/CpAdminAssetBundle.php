<?php

namespace vaersaagod\linkmate\utilities;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Class CpAdminAssetBundle
 *
 * @package vaersaagod\linkmate\utilities
 */
class CpAdminAssetBundle extends AssetBundle
{
    /**
     * @return void
     */
    public function init(): void
    {
        $this->sourcePath = dirname(__DIR__).'/resources';
        $this->depends = [CpAsset::class];
        $this->js = ['LinkFieldAdmin.js'];
        $this->css = ['LinkFieldAdmin.css'];

        parent::init();
    }
}
