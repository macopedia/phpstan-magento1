<?php

declare(strict_types=1);

namespace PHPStanMagento1\Config;

/**
 * This is a simplified version of the \Mage_Core_Model_Config
 * Removed references to Mage:: , so we don't gave to load/execute Mage class
 * in order to load xml configuration, especially translation from
 * magento names (catalog/product) to php class names Mage_Catalog_Model_Product.
 */
class MagentoCore extends \Mage_Core_Model_Config_Base
{
    /**
     * Empty configuration object for loading and megring configuration parts
     *
     * @var \Mage_Core_Model_Config_Base
     */
    protected $_prototype;

    /**
     * Flag which identify what local configuration is loaded
     *
     * @var bool
     */
    protected $_isLocalConfigLoaded = false;

    /**
     * Configuration xml
     *
     * @var \Mage_Core_Model_Config_Element
     */
    protected $_xml = null;

    public function __construct($sourceData = null)
    {
        $this->_prototype = new \Mage_Core_Model_Config_Base();
        parent::__construct($sourceData);
    }

    /**
     * workaround to skip calling getOptions(), so we don't have to override
     * Mage_Core_Model_Config_Options too
     * @return string
     */
    protected function getEtcDir()
    {
        return BP . '/app/etc';
    }

    /**
     * workaround to skip calling getOptions(), so we don't have to override
     * Mage_Core_Model_Config_Options too
     * @return string
     */
    protected function getCodeDir()
    {
        return BP . '/app/code';
    }

    /**
     * Retrieve class name by class group
     *
     * @param   string $groupType currently supported model, block, helper
     * @param   string $classId slash separated class identifier, ex. group/class
     * @param   string $groupRootNode optional config path for group config
     * @return  string
     */
    public function getGroupedClassName($groupType, $classId, $groupRootNode = null)
    {
        if (empty($groupRootNode)) {
            $groupRootNode = 'global/' . $groupType . 's';
        }

        $classArr = explode('/', trim($classId));
        $group = $classArr[0];
        $class = !empty($classArr[1]) ? $classArr[1] : null;

        if (isset($this->_classNameCache[$groupRootNode][$group][$class])) {
            return $this->_classNameCache[$groupRootNode][$group][$class];
        }

        $config = $this->_xml->global->{$groupType . 's'}->{$group};

        // First - check maybe the entity class was rewritten
        $className = '';
        if (isset($config->rewrite->$class)) {
            $className = (string)$config->rewrite->$class;
        } else {
            /**
             * Backwards compatibility for pre-MMDB extensions.
             * In MMDB release resource nodes <..._mysql4> were renamed to <..._resource>. So <deprecatedNode> is left
             * to keep name of previously used nodes, that still may be used by non-updated extensions.
             */
            if (isset($config->deprecatedNode)) {
                $deprecatedNode = $config->deprecatedNode;
                $configOld = $this->_xml->global->{$groupType . 's'}->$deprecatedNode;
                if (isset($configOld->rewrite->$class)) {
                    $className = (string) $configOld->rewrite->$class;
                }
            }
        }

        $className = trim($className);

        // Second - if entity is not rewritten then use class prefix to form class name
        if (empty($className)) {
            if (!empty($config)) {
                $className = $this->getClassName($config);
            }
            if (empty($className)) {
                $className = 'mage_' . $group . '_' . $groupType;
            }
            if (!empty($class)) {
                $className .= '_' . $class;
            }
            $className = uc_words($className);
        }

        $this->_classNameCache[$groupRootNode][$group][$class] = $className;
        return $className;
    }

    /**
     * copied from Mage_Core_Model_Config_Element to avoid calling Mage::
     *
     * @return string
     */
    public function getClassName($config)
    {
        if ($config->class) {
            $model = (string)$config->class;
        } elseif ($config->model) {
            $model = (string)$config->model;
        } else {
            return false;
        }
        return $this->getModelClassName($model);
    }

    /**
     * Retrieve block class name
     *
     * @param   string $blockType
     * @return  string
     */
    public function getBlockClassName($blockType)
    {
        if (strpos($blockType, '/') === false) {
            return $blockType;
        }
        return $this->getGroupedClassName('block', $blockType);
    }

    /**
     * Retrieve helper class name
     *
     * @param   string $helperName
     * @return  string
     */
    public function getHelperClassName($helperName)
    {
        if (strpos($helperName, '/') === false) {
            $helperName .= '/data';
        }
        return $this->getGroupedClassName('helper', $helperName);
    }
    /**
     * Retrieve module class name
     *
     * @param   string $modelClass
     * @return  string
     */
    public function getModelClassName($modelClass)
    {
        $modelClass = trim($modelClass);
        if (strpos($modelClass, '/') === false) {
            return $modelClass;
        }
        return $this->getGroupedClassName('model', $modelClass);
    }

    /**
     * Get factory class name for a resource
     *
     * @param string $modelClass
     * @return string|false
     */
    protected function _getResourceModelFactoryClassName($modelClass)
    {
        $classArray = explode('/', $modelClass);
        if (count($classArray) != 2) {
            return false;
        }

        [$module, $model] = $classArray;
        if (!isset($this->_xml->global->models->{$module})) {
            return false;
        }

        $moduleNode = $this->_xml->global->models->{$module};
        if (!empty($moduleNode->resourceModel)) {
            $resourceModel = (string)$moduleNode->resourceModel;
        } else {
            return false;
        }

        return $resourceModel . '/' . $model;
    }

    /**
     * Get a resource model class name
     *
     * @param string $modelClass
     * @return string|false
     */
    public function getResourceModelClassName($modelClass)
    {
        $factoryName = $this->_getResourceModelFactoryClassName($modelClass);
        if ($factoryName) {
            return $this->getModelClassName($factoryName);
        }
        return false;
    }

    /**
     * Load base system configuration (config.xml and local.xml files)
     *
     * @return $this
     */
    public function loadBase()
    {
        $etcDir = $this->getEtcDir();

        $files = glob($etcDir . DS . '*.xml');

        $this->loadFile(current($files));
        while ($file = next($files)) {
            $merge = clone $this->_prototype;
            $merge->loadFile($file);
            $this->extend($merge);
        }
        if (in_array($etcDir . DS . 'local.xml', $files)) {
            $this->_isLocalConfigLoaded = true;
        }
        return $this;
    }

    /**
     * Load modules configuration
     *
     * @return $this
     */
    public function loadModules()
    {
        $this->_loadDeclaredModules();

        $this->loadModulesConfiguration(['config.xml'], $this);

        /**
         * Prevent local.xml directives overwriting
         */
        $mergeConfig = clone $this->_prototype;
        $this->_isLocalConfigLoaded = $mergeConfig->loadFile($this->getEtcDir() . DS . 'local.xml');
        if ($this->_isLocalConfigLoaded) {
            $this->extend($mergeConfig);
        }

        $this->applyExtends();
        return $this;
    }

    /**
     * Iterate all active modules "etc" folders and combine data from
     * specified xml file name to one object
     *
     * @param string $fileName
     * @param null|\Mage_Core_Model_Config_Base $mergeToObject
     * @param null $mergeModel
     * @return \Mage_Core_Model_Config_Base
     */
    public function loadModulesConfiguration($fileName, $mergeToObject = null, $mergeModel = null)
    {
        $disableLocalModules = false;

        if ($mergeToObject === null) {
            $mergeToObject = clone $this->_prototype;
            $mergeToObject->loadString('<config/>');
        }
        if ($mergeModel === null) {
            $mergeModel = clone $this->_prototype;
        }
        $modules = $this->getNode('modules')->children();
        foreach ($modules as $modName => $module) {
            if ($module->is('active')) {
                if ($disableLocalModules && ('local' === (string)$module->codePool)) {
                    continue;
                }
                if (!is_array($fileName)) {
                    $fileName = [$fileName];
                }

                foreach ($fileName as $configFile) {
                    $configFile = $this->getModuleDir('etc', $modName) . DS . $configFile;
                    if ($mergeModel->loadFile($configFile)) {
                        $this->_makeEventsLowerCase('global', $mergeModel);
                        $this->_makeEventsLowerCase('frontend', $mergeModel);
                        $this->_makeEventsLowerCase('admin', $mergeModel);
                        $this->_makeEventsLowerCase('adminhtml', $mergeModel);

                        $mergeToObject->extend($mergeModel, true);
                    }
                }
            }
        }
        return $mergeToObject;
    }

    /**
     * Get module config node
     *
     * @param string $moduleName
     * @return \Mage_Core_Model_Config_Element|\SimpleXMLElement
     */
    public function getModuleConfig($moduleName = '')
    {
        $modules = $this->getNode('modules');
        if ('' === $moduleName) {
            return $modules;
        } else {
            return $modules->$moduleName;
        }
    }

    /**
     * Get module directory by directory type
     *
     * @param   string $type
     * @param   string $moduleName
     * @return  string
     */
    public function getModuleDir($type, $moduleName)
    {
        $codePool = (string)$this->getModuleConfig($moduleName)->codePool;
        $dir = $this->getCodeDir() . DS . $codePool . DS . uc_words($moduleName, DS);

        switch ($type) {
            case 'etc':
                $dir .= DS . 'etc';
                break;

            case 'controllers':
                $dir .= DS . 'controllers';
                break;

            case 'sql':
                $dir .= DS . 'sql';
                break;
            case 'data':
                $dir .= DS . 'data';
                break;

            case 'locale':
                $dir .= DS . 'locale';
                break;
        }

        $dir = str_replace('/', DS, $dir);
        return $dir;
    }

    /**
     * Load declared modules configuration
     *
     * @param   null $mergeConfig depricated
     * @return  $this|void
     */
    protected function _loadDeclaredModules($mergeConfig = null)
    {
        $moduleFiles = $this->_getDeclaredModuleFiles();
        if (!$moduleFiles) {
            return ;
        }

        $unsortedConfig = new \Mage_Core_Model_Config_Base();
        $unsortedConfig->loadString('<config/>');
        $fileConfig = new \Mage_Core_Model_Config_Base();

        // load modules declarations
        foreach ($moduleFiles as $file) {
            $fileConfig->loadFile($file);
            $unsortedConfig->extend($fileConfig);
        }

        $moduleDepends = [];
        foreach ($unsortedConfig->getNode('modules')->children() as $moduleName => $moduleNode) {
            $depends = [];
            if ($moduleNode->depends) {
                foreach ($moduleNode->depends->children() as $depend) {
                    $depends[$depend->getName()] = true;
                }
            }
            $moduleDepends[$moduleName] = [
                'module'    => $moduleName,
                'depends'   => $depends,
                'active'    => (string)$moduleNode->active === 'true',
            ];
        }

        // check and sort module dependence
        $moduleDepends = $this->_sortModuleDepends($moduleDepends);

        // create sorted config
        $sortedConfig = new \Mage_Core_Model_Config_Base();
        $sortedConfig->loadString('<config><modules/></config>');

        foreach ($unsortedConfig->getNode()->children() as $nodeName => $node) {
            if ($nodeName !== 'modules') {
                $sortedConfig->getNode()->appendChild($node);
            }
        }

        foreach ($moduleDepends as $moduleProp) {
            $node = $unsortedConfig->getNode('modules/' . $moduleProp['module']);
            $sortedConfig->getNode('modules')->appendChild($node);
        }

        $this->extend($sortedConfig);

        return $this;
    }

    /**
     * Sort modules and check depends
     *
     * @param array $modules
     * @return array
     */
    protected function _sortModuleDepends($modules)
    {
        foreach ($modules as $moduleName => $moduleProps) {
            $depends = $moduleProps['depends'];
            foreach ($moduleProps['depends'] as $depend => $true) {
                if ($moduleProps['active'] && ((!isset($modules[$depend])) || empty($modules[$depend]['active']))) {
                    throw new \Exception(
                        \sprintf('Module "%1$s" requires module "%2$s".', $moduleName, $depend)
                    );
                }
                $depends = array_merge($depends, $modules[$depend]['depends']);
            }
            $modules[$moduleName]['depends'] = $depends;
        }
        $modules = array_values($modules);

        $size = count($modules) - 1;
        for ($i = $size; $i >= 0; $i--) {
            for ($j = $size; $i < $j; $j--) {
                if (isset($modules[$i]['depends'][$modules[$j]['module']])) {
                    $value       = $modules[$i];
                    $modules[$i] = $modules[$j];
                    $modules[$j] = $value;
                }
            }
        }

        $definedModules = [];
        foreach ($modules as $moduleProp) {
            foreach ($moduleProp['depends'] as $dependModule => $true) {
                if (!isset($definedModules[$dependModule])) {
                    throw new \Exception(
                        \sprintf('Module "%1$s" cannot depend on "%2$s".', $moduleProp['module'], $dependModule)
                    );
                }
            }
            $definedModules[$moduleProp['module']] = true;
        }

        return $modules;
    }

    /**
     * Retrive Declared Module file list
     *
     * @return array|false
     */
    protected function _getDeclaredModuleFiles()
    {
        $etcDir = $this->getEtcDir();
        $moduleFiles = glob($etcDir . DS . 'modules' . DS . '*.xml');

        if (!$moduleFiles) {
            return false;
        }

        $collectModuleFiles = [
            'base'   => [],
            'mage'   => [],
            'custom' => []
        ];

        foreach ($moduleFiles as $v) {
            $name = explode(DIRECTORY_SEPARATOR, $v);
            $name = substr($name[count($name) - 1], 0, -4);

            if ($name === 'Mage_All') {
                $collectModuleFiles['base'][] = $v;
            } elseif (substr($name, 0, 5) === 'Mage_') {
                $collectModuleFiles['mage'][] = $v;
            } else {
                $collectModuleFiles['custom'][] = $v;
            }
        }

        return array_merge(
            $collectModuleFiles['base'],
            $collectModuleFiles['mage'],
            $collectModuleFiles['custom']
        );
    }

    /**
     * Makes all events to lower-case
     *
     * @param string $area
     * @param \Varien_Simplexml_Config $mergeModel
     */
    protected function _makeEventsLowerCase($area, \Varien_Simplexml_Config $mergeModel)
    {
        $events = $mergeModel->getNode($area . "/" . \Mage_Core_Model_App_Area::PART_EVENTS);
        if ($events !== false) {
            $children = clone $events->children();
            /** @var \Mage_Core_Model_Config_Element $event */
            foreach ($children as $event) {
                if ($this->_isNodeNameHasUpperCase($event)) {
                    $oldName = $event->getName();
                    $newEventName = strtolower($oldName);
                    if (!isset($events->$newEventName)) {
                        /** @var \Mage_Core_Model_Config_Element $newNode */

                        $newNode = $events->addChild($newEventName);
                        $newNode->extend($event);
                    }
                    unset($events->$oldName);
                }
            }
        }
    }

    /**
     * Checks is event name has upper-case letters
     *
     * @param \Mage_Core_Model_Config_Element $event
     * @return bool
     */
    protected function _isNodeNameHasUpperCase(\Mage_Core_Model_Config_Element $event)
    {
        return (strtolower($event->getName()) !== (string)$event->getName());
    }
}
