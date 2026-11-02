<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_mycompanymap
 *
 * @copyright   Copyright (C) 2025 Dharma design. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Module\Mycompanymap\Site\Dispatcher;

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

/**
 * Dispatcher class for mod_mycompanymap
 *
 * @since  1.0.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Returns the layout data.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        $helper = $this->getHelperFactory()->getHelper('MycompanymapHelper');

        // Get companies and unique cities
        $data['companies'] = $helper->getCompanies($data['params']);
        $data['uniqueCities'] = $helper->getUniqueCities($data['companies']);
        $data['scriptOptions'] = $helper->getScriptOptions($data['companies'], $data['params'], $this->module->id);

        return $data;
    }
}
