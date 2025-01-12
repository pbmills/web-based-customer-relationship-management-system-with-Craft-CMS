<?php

namespace foster\fostercrm;

use Craft;
use craft\base\Plugin as BasePlugin;

/**
 * Foster CRM plugin
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '1.0.0';

    public function init(): void
{
    parent::init();

    // Register translation category
    Craft::$app->i18n->translations['fostercrm*'] = [
        'class' => \craft\i18n\PhpMessageSource::class,
        'sourceLanguage' => 'en',
        'basePath' => __DIR__ . '/translations',
        'forceTranslation' => true,
    ];

    Craft::info(
        Craft::t('fostercrm', 'plugin loaded'),
        __METHOD__
    );
}

}
