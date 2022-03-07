<?php
declare(strict_types=1);

use PHPStanMagento1\Autoload\Magento\ModuleControllerAutoloader;

/**
 * @var $container \PHPStan\DependencyInjection\MemoizingContainer
 */
$magentoRootPath = $container->getParameter('magentoRootPath');
if (empty($magentoRootPath)) {
    throw new \Exception('Please set "magentoRootPath" in your phpstan.neon.');
}

if (!defined('BP')) {
    define('BP', $magentoRootPath);
}

(new ModuleControllerAutoloader('local'))->register();
(new ModuleControllerAutoloader('core'))->register();
(new ModuleControllerAutoloader('community'))->register();

/**
 * We replace the original Varien_Autoload autoloader with a custom one in order to prevent errors with invalid classes
 * that are used throughout the Magento core code.
 * The original autoloader would in this case return false and lead to an error in phpstan because the type alias in extension.neon
 * is evaluated afterwards.
 *
 * @see \Varien_Autoload::autoload()
 */
spl_autoload_register(static function($className) {
    spl_autoload_unregister([Varien_Autoload::instance(), 'autoload']);

    $classFile = str_replace(' ', DIRECTORY_SEPARATOR, ucwords(str_replace('_', ' ', $className)));
    $classFile .= '.php';

    foreach (explode(':', get_include_path()) as $path) {
        if (\file_exists($path . DIRECTORY_SEPARATOR . $classFile)) {
            return include $classFile;
        }
    }
}, true, true);
