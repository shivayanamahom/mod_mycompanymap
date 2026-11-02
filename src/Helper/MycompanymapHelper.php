<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_mycompanymap
 *
 * @copyright   Copyright (C) 2025 Dharma design. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\Mycompanymap\Site\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

/**
 * Helper class for mod_mycompanymap module
 *
 * @since  1.0.0
 */
class MycompanymapHelper
{
    /**
     * Get the list of company items.
     *
     * @param   Registry  $params  The module parameters.
     *
     * @return  array  An array of company objects.
     *
     * @since   1.0.0
     */
    public function getCompanies(Registry $params): array
    {
        $itemsData = $params->get('company_items');
        $companies = [];

        // Handle data stored as JSON string or native PHP array/object
        if (is_array($itemsData) || is_object($itemsData)) {
            $items = is_array($itemsData) ? $itemsData : (array) $itemsData;
        } elseif (is_string($itemsData) && !empty($itemsData)) {
            $items = json_decode($itemsData, true) ?: [];
        } else {
            $items = [];
        }

        $id = 1;
        foreach ($items as $item) {
            $company = (object) $item;

            // Parse coordinates: "lat,lng" -> [lng, lat] (Yandex Maps API 3.0 format)
            $coords = [];
            if (!empty($company->company_coords)) {
                $tmp = array_map('trim', explode(',', $company->company_coords));
                if (count($tmp) === 2 && is_numeric($tmp[0]) && is_numeric($tmp[1])) {
                    $lat = (float) $tmp[0];
                    $lng = (float) $tmp[1];
                    if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                        $coords = [$lng, $lat];
                    }
                }
            }
            $company->coords = $coords;

            // Unique ID for JS and cards
            $company->id = $id++;

            // City (for filtering)
            $company->city = isset($company->company_city) ? trim($company->company_city) : '';

            // Name
            $company->name = isset($company->company_name) ? trim($company->company_name) : '';

            $companies[] = $company;
        }

        return $companies;
    }

    /**
     * Get unique cities from the company list.
     *
     * @param   array  $companies  Array of company objects.
     *
     * @return  array  An array of unique city names, sorted alphabetically.
     *
     * @since   1.0.0
     */
    public function getUniqueCities(array $companies): array
    {
        $cities = [];
        foreach ($companies as $company) {
            if (!empty($company->city)) {
                $cities[] = $company->city;
            }
        }

        $uniqueCities = array_unique($cities);
        sort($uniqueCities, SORT_LOCALE_STRING | SORT_FLAG_CASE);

        return $uniqueCities;
    }

    /**
     * Prepare data for JavaScript.
     *
     * @param   array     $companies  Array of company objects.
     * @param   Registry  $params     Module parameters.
     * @param   int       $moduleId   Module ID.
     *
     * @return  array  Data ready for JSON encoding.
     *
     * @since   1.0.0
     */
    public function getScriptOptions(array $companies, Registry $params, int $moduleId): array
    {
        // Map center and zoom
        $center = $params->get('map_default_center', '55.751574, 37.573856');
        $centerArr = array_map('trim', explode(',', $center));
        $defaultCenter = (count($centerArr) === 2)
            ? [floatval($centerArr[1]), floatval($centerArr[0])] // lng, lat for API 3.0
            : [37.573856, 55.751574];

        $defaultZoom = (int) $params->get('map_default_zoom', 10);

        // Icon color mapping
        $iconStyle = $params->get('icon_style', 'islands#blueIcon');
        $iconColors = [
            'islands#blueIcon' => '#4DA6FF',
            'islands#redIcon' => '#FF4545',
            'islands#greenIcon' => '#42D65C',
            'islands#violetIcon' => '#BF80FF',
            'islands#darkOrangeIcon' => '#FF8C1A'
        ];

        return [
            'mapId' => 'mycompanymap-map-' . $moduleId,
            'filterId' => 'mycompanymap-filter-' . $moduleId,
            'cardsContainerId' => 'mycompanymap-cards-' . $moduleId,
            'defaultCenter' => $defaultCenter,
            'defaultZoom' => $defaultZoom,
            'companies' => $companies,
            'lang' => Factory::getLanguage()->getTag() === 'ru-RU' ? 'ru_RU' : 'en_US',
            'icon_color' => $iconColors[$iconStyle] ?? '#4DA6FF',
            'messages' => ['loadError' => Text::_('MOD_MYCOMPANYMAP_MAP_ERROR'), 'close' => Text::_('MOD_MYCOMPANYMAP_CLOSE')],
        ];
    }
}
