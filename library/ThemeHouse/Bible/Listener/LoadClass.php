<?php

class ThemeHouse_Bible_Listener_LoadClass extends ThemeHouse_Listener_LoadClass
{

    protected function _getExtendedClasses()
    {
        return array(
            'ThemeHouse_Bible' => array(
                'bb_code' => array(
                    'XenForo_BbCode_Formatter_BbCode_AutoLink',
                    'XenForo_BbCode_Formatter_Base'
                ),
                'datawriter' => array(
                    'XenForo_DataWriter_DiscussionMessage_Post'
                ),
            ),
        );
    }

    public static function loadClassBbCode($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_Bible_Listener_LoadClass', $class, $extend, 'bb_code');
    }

    public static function loadClassDataWriter($class, array &$extend)
    {
        $extend = self::createAndRun('ThemeHouse_Bible_Listener_LoadClass', $class, $extend, 'datawriter');
    }
}