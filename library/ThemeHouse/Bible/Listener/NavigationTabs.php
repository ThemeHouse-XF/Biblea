<?php

class ThemeHouse_Bible_Listener_NavigationTabs extends ThemeHouse_Listener_NavigationTabs
{

    /**
     * Gets the navigation tabs for this add-on. See parent for explanation.
     *
     * @return array
     */
    protected function _getNavigationTabs()
    {
        return array(
            'bible' => array(
                'title' => new XenForo_Phrase('th_bible_bible'),
                'href' => XenForo_Link::buildPublicLink('bible'),
                'position' => 'middle',
                'linksTemplate' => 'th_navigation_tabs_bible'
            )
        );
    }

    public static function navigationTabs(array &$extraTabs, $selectedTabId)
    {
        $navigationTabsModel = new ThemeHouse_Bible_Listener_NavigationTabs($extraTabs, $selectedTabId);
        $extraTabs = $navigationTabsModel->run();
    }
}