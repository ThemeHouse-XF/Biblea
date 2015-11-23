<?php

class ThemeHouse_Bible_Listener_FileHealthCheck
{

    public static function fileHealthCheck(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
    {
        $hashes = array_merge($hashes,
            array(
                'library/ThemeHouse/Bible/BbCode/Verse.php' => 'ded4bd9cfa79056379450d0f1a0b95ed',
                'library/ThemeHouse/Bible/ControllerAdmin/Bible.php' => '0a1bc661173c0b3e6f5e2e81944d51a1',
                'library/ThemeHouse/Bible/ControllerAdmin/Book.php' => 'f45077433012277f563cc16c15d54015',
                'library/ThemeHouse/Bible/ControllerPublic/Bible.php' => '616db77c34b42d25ad7247e9149c6800',
                'library/ThemeHouse/Bible/DataWriter/Bible.php' => '2d7e28d4e5e64a28b0b4f7727d7dfa68',
                'library/ThemeHouse/Bible/DataWriter/Book.php' => 'e170c11ee8537e5fbe20e4d6cec7bacd',
                'library/ThemeHouse/Bible/DataWriter/Verse.php' => 'dc885563092c874b4ddc883c3873c3b4',
                'library/ThemeHouse/Bible/Deferred/Import.php' => '91676c00d49a40dee6df39046c443b76',
                'library/ThemeHouse/Bible/Deferred/PostVerse.php' => 'a5e64a6882cc0bcc7ff2acdb11e9a3e2',
                'library/ThemeHouse/Bible/Deferred/VerseDelete.php' => '6907900b278584d733577593eca427d4',
                'library/ThemeHouse/Bible/Extend/XenForo/BbCode/Formatter/Base.php' => 'db9fa0e570d1306a93e435381d5c4e8d',
                'library/ThemeHouse/Bible/Extend/XenForo/BbCode/Formatter/BbCode/AutoLink.php' => 'c1963c7e0b06bfb6eb85100ed29ebe65',
                'library/ThemeHouse/Bible/Extend/XenForo/DataWriter/DiscussionMessage/Post.php' => '4f392556a04e32ffd0ed3f495e0a8a53',
                'library/ThemeHouse/Bible/Install/Controller.php' => '9a046097c2a39a6e376a694e8e3bb0f2',
                'library/ThemeHouse/Bible/Listener/LoadClass.php' => '2987aeb1471d0e9561ae73b8cc4eb375',
                'library/ThemeHouse/Bible/Listener/NavigationTabs.php' => 'fa6c6c1a6a8e21cb21f023c0c3e0f11f',
                'library/ThemeHouse/Bible/Model/Bible.php' => 'aca7706a58e3564cb709f1420b7b65ea',
                'library/ThemeHouse/Bible/Model/Book.php' => '5321c1f5d0b8dc2a9ae0ae7cd254dd8e',
                'library/ThemeHouse/Bible/Model/Verse.php' => 'efa447ee04dbb437a88f96a2dc10cb7a',
                'library/ThemeHouse/Bible/Option/BibleChooser.php' => 'ccb60f2e6edb869ef160ac54742aef79',
                'library/ThemeHouse/Bible/Route/Prefix/Bible.php' => '3f600c52434a118febf7cb3428b3de52',
                'library/ThemeHouse/Bible/Route/PrefixAdmin/BibleBooks.php' => '06a6d2851303049602b76f45f0845301',
                'library/ThemeHouse/Bible/Route/PrefixAdmin/Bibles.php' => '400cb4954e904e3304f2a30d4495474a',
                'library/ThemeHouse/Bible/Search/DataHandler/Verse.php' => '115a30ad4d629489e11d9292e4747fd1',
                'library/ThemeHouse/Bible/SitemapHandler/Verse.php' => '3083df03c15e90cb4fbcd7c490267ee0',
                'library/ThemeHouse/Bible/ViewPublic/Bible/View.php' => 'a4d5fbe9a90e51b8618fb81a24133cdd',
                'library/ThemeHouse/Bible/ViewPublic/Verse/Tooltip.php' => 'afc462972f44b9e18b63fb1debf96e51',
                'library/ThemeHouse/Install.php' => '18f1441e00e3742460174ab197bec0b7',
                'library/ThemeHouse/Install/20151109.php' => '2e3f16d685652ea2fa82ba11b69204f4',
                'library/ThemeHouse/Deferred.php' => 'ebab3e432fe2f42520de0e36f7f45d88',
                'library/ThemeHouse/Deferred/20150106.php' => 'a311d9aa6f9a0412eeba878417ba7ede',
                'library/ThemeHouse/Listener/ControllerPreDispatch.php' => 'fdebb2d5347398d3974a6f27eb11a3cd',
                'library/ThemeHouse/Listener/ControllerPreDispatch/20150911.php' => 'f2aadc0bd188ad127e363f417b4d23a9',
                'library/ThemeHouse/Listener/InitDependencies.php' => '8f59aaa8ffe56231c4aa47cf2c65f2b0',
                'library/ThemeHouse/Listener/InitDependencies/20150212.php' => 'f04c9dc8fa289895c06c1bcba5d27293',
                'library/ThemeHouse/Listener/LoadClass.php' => '5cad77e1862641ddc2dd693b1aa68a50',
                'library/ThemeHouse/Listener/LoadClass/20150518.php' => 'f4d0d30ba5e5dc51cda07141c39939e3',
                'library/ThemeHouse/Listener/NavigationTabs.php' => '68240ed2ca8e53f5c177997ea265d3b7',
                'library/ThemeHouse/Listener/NavigationTabs/20150106.php' => '5bffa2f8f925136f3277c867262c1d8d',
            ));
    }
}