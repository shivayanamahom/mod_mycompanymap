<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_mycompanymap
 *
 * @copyright   Copyright (C) 2025 Dharma design. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module as ModuleServiceProvider;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The module service provider.
 *
 * @since  1.0.0
 */
return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\Joomla\\Module\\Mycompanymap'));
        $container->registerServiceProvider(new HelperFactory('\\Joomla\\Module\\Mycompanymap\\Site\\Helper'));
        $container->registerServiceProvider(new ModuleServiceProvider());
    }
};
