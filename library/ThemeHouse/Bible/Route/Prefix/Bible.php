<?php

/**
 * Route prefix handler for bible in the public system.
 */
class ThemeHouse_Bible_Route_Prefix_Bible implements XenForo_Route_Interface
{

    /**
     * Match a specific route for an already matched prefix.
     *
     * @see XenForo_Route_Interface::match()
     */
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
        $components = explode('/', $routePath);
        
        if (isset($components[1]) && preg_match('#^([0-9]*)$#', $components[1], $matches)) {
            $request->setParam('chapter', $components[1]);
            $request->setParam('url_portion', $components[0]);
            unset($components[0], $components[1]);
            $action = implode('', $components);
        } elseif (isset($components[1]) && preg_match('#^([0-9]*):([0-9]*)$#', $components[1], $matches)) {
            $request->setParam('chapter', $matches[1]);
            $request->setParam('verse', $matches[2]);
            $request->setParam('url_portion', $components[0]);
            unset($components[0], $components[1]);
            $action = implode('', $components);
        } elseif (isset($components[1]) && preg_match('#^([0-9]*):([0-9]*)-([0-9]*)$#', $components[1], $matches)) {
            $request->setParam('chapter', $matches[1]);
            $request->setParam('verse', $matches[2]);
            $request->setParam('verse_to', $matches[3]);
            $request->setParam('url_portion', $components[0]);
            unset($components[0], $components[1]);
            $action = implode('', $components);
        } else {
            $action = $router->resolveActionWithStringParam($routePath, $request, 'url_portion');
        }
            
        return $router->getRouteMatch('ThemeHouse_Bible_ControllerPublic_Bible', $action, 'bible');
    }

    /**
     * Method to build a link to the specified page/action with the provided
     * data and params.
     *
     * @see XenForo_Route_BuilderInterface
     */
    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        if (!empty($data['chapter'])) {
            if (!empty($data['verse'])) {
                if (!empty($data['verse_to'])) {
                    $action = $data['chapter'] . ':' . $data['verse'] . '-' . $data['verse_to'] . '/' .
                         $action;
                } else {
                    $action = $data['chapter'] . ':' . $data['verse'] . '/' . $action;
                }
            } else {
                $action = $data['chapter'] . '/' . $action;
            }
        }
        
        $xenOptions = XenForo_Application::get('options');
        if (isset($extraParams['bible_id']) && $extraParams['bible_id'] == $xenOptions->th_bible_defaultBible) {
            unset($extraParams['bible_id']);
        }
        
        return XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, $data, 'url_portion');
    }
}