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
use Joomla\CMS\Helper\ModuleHelper;

/**
 * Entry point for mod_mycompanymap module.
 *
 * @var  Joomla\Registry\Registry  $params  Module parameters
 * @var  stdClass                  $module  Module object
 * @var  Joomla\CMS\Application\CMSApplication  $app  Application object
 */

// Load module language files
$language = Factory::getLanguage();
$language->load('mod_mycompanymap', JPATH_SITE);

// Load helper class
require_once __DIR__ . '/src/Helper/MycompanymapHelper.php';

// Get helper instance
$helper = new \Joomla\Module\Mycompanymap\Site\Helper\MycompanymapHelper();

// Get data
$companies = $helper->getCompanies($params);
$uniqueCities = $helper->getUniqueCities($companies);
$scriptOptions = $helper->getScriptOptions($companies, $params, $module->id);

require ModuleHelper::getLayoutPath('mod_mycompanymap', $params->get('layout', 'default'));
