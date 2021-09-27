<?php
declare(strict_types=1);

use PHPStanMagento1\Autoload\Magento\ModuleControllerAutoloader;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('PS')) {
    define('PS', PATH_SEPARATOR);
}

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

/**
 * Set include path
 */
$paths = [];
$paths[] = BP . DS . 'app' . DS . 'code' . DS . 'local';
$paths[] = BP . DS . 'app' . DS . 'code' . DS . 'community';
$paths[] = BP . DS . 'app' . DS . 'code' . DS . 'core';
$paths[] = BP . DS . 'lib';

$appPath = implode(PS, $paths);
set_include_path($appPath . PS . get_include_path());
include_once "Mage/Core/functions.php";

(new ModuleControllerAutoloader('local'))->register();
(new ModuleControllerAutoloader('core'))->register();
(new ModuleControllerAutoloader('community'))->register();

/**
 * Custom autoloader compatible with Varien_Autoload
 * Autoloading is needed only for the PHPStanMagento1\Config\MagentoCore which inherits from some magento classes.
 * PHPStan uses static analysis, so doesn't require autoloading.
 */
spl_autoload_register(static function($className) {

    $classFile = str_replace(' ', DIRECTORY_SEPARATOR, ucwords(str_replace('_', ' ', $className)));
    $classFile .= '.php';

    foreach (explode(':', get_include_path()) as $path) {
        if (\file_exists($path . DIRECTORY_SEPARATOR . $classFile)) {
            return include $classFile;
        }
    }
}, true, true);
