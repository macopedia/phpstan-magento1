<?php declare(strict_types=1);

namespace PHPStanMagento1\Config;

use Mage;
use Mage_Core_Model_Config;

final class MageCoreConfig
{
    private bool $useLocalXml;
    private bool $hasInitialized = false;

    public function __construct(bool $useLocalXml)
    {
        $this->useLocalXml = $useLocalXml;
    }

    public function getConfig(): Mage_Core_Model_Config
    {
        if ($this->hasInitialized === false && $this->useLocalXml === false) {
            $this->hasInitialized = true;
            Mage::init('', 'store', ['is_installed' => false]);
        }
        return Mage::app()->getConfig();
    }

    /**
     * @return ?callable(string): (string|false)
     */
    public function getClassNameConverterFunction(string $class, string $method): ?callable
    {
        switch ("$class::$method") {
        case 'Mage::getModel':
        case 'Mage::getSingleton':
        case 'Mage_Core_Model_Config::getModelInstance':
            return fn (string $alias) => $this->getConfig()->getModelClassName($alias);
        case 'Mage::getResourceModel':
        case 'Mage::getResourceSingleton':
        case 'Mage_Core_Model_Config::getResourceModelInstance':
            return fn (string $alias) => $this->getConfig()->getResourceModelClassName($alias);
        case 'Mage::getResourceHelper':
        case 'Mage_Core_Model_Config::getResourceHelper':
        case 'Mage_Core_Model_Config::getResourceHelperInstance':
            return fn (string $alias) => $this->getConfig()->getResourceHelperClassName($alias);
        case 'Mage_Core_Model_Layout::createBlock':
        case 'Mage_Core_Model_Layout::getBlockSingleton':
            return fn (string $alias) => $this->getConfig()->getBlockClassName($alias);
        case 'Mage::helper':
        case 'Mage_Core_Model_Layout::helper':
        case 'Mage_Core_Block_Abstract::helper':
        case 'Mage_Core_Model_Config::getHelperInstance':
            return fn (string $alias) => $this->getConfig()->getHelperClassName($alias);
        case 'Mage_Core_Model_Config::getNodeClassInstance':
            return fn (string $path) => $this->getConfig()->getNodeClassName($path);
        case 'Mage_Admin_Model_User::_helper':
        case 'Mage_Adminhtml_Controller_Rss_Abstract::_helper':
        case 'Mage_Api_Model_User::_helper':
        case 'Mage_Customer_AccountController::_helper':
        case 'Mage_Customer_Model_Customer::_helper':
        case 'Mage_Rss_Controller_Abstract::_helper':
        case 'Mage_SalesRule_Model_Validator::_helper':
        case 'Mage_Weee_Helper_Data::_helper':
        case 'Mage_Weee_Model_Config_Source_Fpt_Tax::_helper':
            // Deprecated _helper calls
            return fn (string $alias) => $this->getConfig()->getHelperClassName($alias);
        }
        return null;
    }
}
