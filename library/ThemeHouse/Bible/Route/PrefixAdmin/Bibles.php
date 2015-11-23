<?php

/**
 * Route prefix handler for bibles in the admin control panel.
 */
class ThemeHouse_Bible_Route_PrefixAdmin_Bibles implements XenForo_Route_Interface
{

    /**
     * Match a specific route for an already matched prefix.
     *
     * @see XenForo_Route_Interface::match()
     */
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $action = $router->resolveActionWithStringParam($routePath, $request, 'bible_id');
        return $router->getRouteMatch('ThemeHouse_Bible_ControllerAdmin_Bible', $action, 'bibles');
    }

    /**
     * Method to build a link to the specified page/action with the provided
     * data and params.
     *
     * @see XenForo_Route_BuilderInterface
     */
    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        return XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, $data, 'bible_id');
    }
}