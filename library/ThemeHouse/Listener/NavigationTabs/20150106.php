<?php

abstract class ThemeHouse_Listener_NavigationTabs
{

    protected $_extraTabs = array();

    protected $_selectedTabId;

    /**
     *
     * @param array $extraTabs
     * @param $selectedTabId
     */
    public function __construct(array &$extraTabs, $selectedTabId)
    {
        $this->_extraTabs = $extraTabs;
        $this->_selectedTabId = $selectedTabId;
    }

    public function run()
    {
        $this->_extraTabs = array_merge($this->_extraTabs, $this->_getNavigationTabs());
        
        return $this->_extraTabs;
    }

    protected function _run()
    {
        try {
            return $this->run();
        } catch (Exception $e) {
            // do nothing
        }
    }

    /**
     * Gets the specified model object from the cache.
     * If it does not exist,
     * it will be instantiated.
     *
     * @param string $class Name of the class to load
     *
     * @return XenForo_Model
     */
    public function getModelFromCache($class)
    {
        if (!isset($this->_modelCache[$class])) {
            $this->_modelCache[$class] = XenForo_Model::create($class);
        }
        
        return $this->_modelCache[$class];
    }
    
    // This only works on PHP 5.3+, so method should be overridden for now
    public static function navigationTabs(array &$extraTabs, $selectedTabId)
    {
        $class = get_called_class();
        $navigationTabs = new $class($extraTabs, $selectedTabId);
        $extraTabs = $navigationTabs->run();
    }

    abstract protected function _getNavigationTabs();

    /**
     * Factory method to get the named navigation tabs listener.
     * The class must exist or be autoloadable or an exception will be thrown.
     *
     * @param string $className Class to load
     * @param array $extraTabs
     * @param $selectedTabId
     *
     * @return ThemeHouse_Listener_NavigationTabs
     */
    public static function create($className, array &$extraTabs, $selectedTabId)
    {
        $createClass = XenForo_Application::resolveDynamicClass($className, 'listener_th');
        if (!$createClass) {
            throw new XenForo_Exception("Invalid listener '$className' specified");
        }
        
        return new $createClass($extraTabs, $selectedTabId);
    }

    /**
     *
     * @param string $className Class to load
     * @param array $extraTabs
     * @param $selectedTabId
     *
     * @return array $extraTabs
     */
    public static function createAndRun($className, array &$extraTabs, $selectedTabId)
    {
        $createClass = self::create($className, $extraTabs, $selectedTabId);
        
        if (XenForo_Application::debugMode()) {
            return $createClass->run();
        }
        try {
            return $createClass->run();
        } catch (Exception $e) {
            return $this->_extraTabs;
        }
    }
}