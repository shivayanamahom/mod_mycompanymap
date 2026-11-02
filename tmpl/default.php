<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_mycompanymap
 *
 * @copyright   Copyright (C) 2025 Dharma design. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * @var  Joomla\CMS\WebAsset\WebAssetManager $wa
 * @var  array                                $companies       Array of company objects
 * @var  array                                $uniqueCities    Array of unique city names
 * @var  array                                $scriptOptions   Options for JavaScript
 * @var  Joomla\Registry\Registry             $params          Module parameters
 * @var  stdClass                             $module          Module object
 */

$wa = $app->getDocument()->getWebAssetManager();
$moduleId = $module->id;

// Check API key
$apiKey = $params->get('yandex_api_key');
if (empty($apiKey)) {
    $user = $app->getIdentity();
    if ($user && $user->authorise('core.edit', 'com_modules')) {
        $app->enqueueMessage(Text::_('MOD_MYCOMPANYMAP_ERROR_NO_API_KEY'), 'warning');
    }
    return;
}

// Register and use module CSS
$wa->registerAndUseStyle(
    'mod_mycompanymap.site',
    'mod_mycompanymap/mycompanymap.css',
    ['version' => '2.0.3'],
    [],
    []
);

// Register Yandex Maps API
$yandexApiUrl = 'https://api-maps.yandex.ru/v3/?apikey=' . rawurlencode((string) $apiKey)
              . '&lang=' . rawurlencode($scriptOptions['lang'] ?? 'ru_RU');

$wa->registerScript(
    'yandex.maps.api',
    $yandexApiUrl,
    [],
    ['defer' => true],
    []
);

// Register module JavaScript
$wa->registerAndUseScript(
    'mod_mycompanymap.site',
    'mod_mycompanymap/mycompanymap.js',
    ['version' => '2.0.3'],
    ['defer' => true],
    ['yandex.maps.api']
);

// Pass options to JavaScript
$wa->addInlineScript(
    'window.modMyCompanyMapOptions' . $moduleId . ' = ' . json_encode($scriptOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';',
    [],
    ['position' => 'before'],
    ['mod_mycompanymap.site']
);

$mapContainerId = $scriptOptions['mapId'];
$cardsContainerId = $scriptOptions['cardsContainerId'];
$filterId = $scriptOptions['filterId'];
?>

<div class="my-company-map-module" id="mod-mycompanymap-<?php echo $moduleId; ?>">

    <?php if (!empty($uniqueCities)) : ?>
        <div class="city-filter-container">
            <label for="<?php echo $filterId; ?>"><?php echo Text::_('MOD_MYCOMPANYMAP_FILTER_BY_CITY_LABEL'); ?></label>
            <select id="<?php echo $filterId; ?>">
                <option value="all"><?php echo Text::_('MOD_MYCOMPANYMAP_FILTER_ALL_CITIES'); ?></option>
                <?php foreach ($uniqueCities as $city) : ?>
                    <option value="<?php echo htmlspecialchars($city, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($city, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="map-container">
        <div id="<?php echo htmlspecialchars($mapContainerId); ?>"
             style="width: <?php echo htmlspecialchars($params->get('map_width', '100%'), ENT_QUOTES, 'UTF-8'); ?>; height: <?php echo htmlspecialchars($params->get('map_height', '400px'), ENT_QUOTES, 'UTF-8'); ?>;">
            <div class="map-loading-placeholder"><?php echo Text::_('MOD_MYCOMPANYMAP_MAP_LOADING'); ?></div>
        </div>
    </div>

    <?php if (!empty($companies)) : ?>
        <div class="company-cards-container" id="<?php echo htmlspecialchars($cardsContainerId); ?>">
            <?php foreach ($companies as $company) : ?>
                <?php
                    $coordsStr = trim($company->company_coords ?? '');
                    $coordsParts = array_map('trim', explode(',', $coordsStr));
                    if (count($coordsParts) !== 2 || !is_numeric($coordsParts[0]) || !is_numeric($coordsParts[1])) {
                        continue;
                    }
                    $coordsAttr = htmlspecialchars($coordsStr, ENT_QUOTES, 'UTF-8');
                    $cityAttr = htmlspecialchars($company->company_city ?? '', ENT_QUOTES, 'UTF-8');
                    $cardId = 'company-card-' . $moduleId . '-' . $company->id;
                ?>
                <div class="company-card"
                     id="<?php echo $cardId; ?>"
                     data-city="<?php echo $cityAttr; ?>"
                     data-coords="<?php echo $coordsAttr; ?>"
                     data-placemarkid="<?php echo $company->id; ?>">
                    <?php if (!empty($company->company_name)) : ?>
                        <h4 class="company-name"><?php echo htmlspecialchars($company->company_name); ?></h4>
                    <?php endif; ?>
                    <?php if (!empty($company->company_address)) : ?>
                        <p class="company-address">
                            <strong><?php echo Text::_('MOD_MYCOMPANYMAP_FIELD_ADDRESS_LABEL'); ?>:</strong>
                            <?php echo htmlspecialchars($company->company_address); ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($company->company_phone)) : ?>
                        <p class="company-phone">
                            <strong><?php echo Text::_('MOD_MYCOMPANYMAP_FIELD_PHONE_LABEL'); ?>:</strong>
                            <?php echo htmlspecialchars($company->company_phone); ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($company->company_city)) : ?>
                        <p class="company-city">
                            <strong><?php echo Text::_('MOD_MYCOMPANYMAP_FIELD_CITY_LABEL'); ?>:</strong>
                            <?php echo htmlspecialchars($company->company_city); ?>
                        </p>
                    <?php endif; ?>
                    <button type="button" class="center-on-map-btn"
                            data-coords="<?php echo $coordsAttr; ?>"
                            data-placemarkid="<?php echo $company->id; ?>">
                        <?php echo Text::_('MOD_MYCOMPANYMAP_SHOW_ON_MAP'); ?>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p><?php echo Text::_('MOD_MYCOMPANYMAP_NO_COMPANIES'); ?></p>
    <?php endif; ?>

</div>
