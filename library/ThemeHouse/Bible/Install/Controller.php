<?php

class ThemeHouse_Bible_Install_Controller extends ThemeHouse_Install
{

    protected $_resourceManagerUrl = 'https://xenforo.com/community/resources/bible.4154/';

    protected $_minVersionId = 1030000;

    protected $_minVersionString = '1.3.0';

    protected function _getTables()
    {
        return array(
            'xf_bible' => array(
                'bible_id' => 'varchar(50) NOT NULL PRIMARY KEY',
                'name' => 'varchar(255) NOT NULL',
                'copyright' => 'varchar(255) NOT NULL DEFAULT \'\'',
                'abbreviation' => 'varchar(255) NOT NULL DEFAULT \'\'',
                'language' => 'varchar(10) NOT NULL DEFAULT \'\'',
                'note' => 'varchar(255) NOT NULL DEFAULT \'\'',
                'last_modified' => 'int unsigned NOT NULL DEFAULT 0'
            ),
            'xf_bible_verse' => array(
                'verse_id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'bible_id' => 'varchar(50) NOT NULL',
                'book_id' => 'int UNSIGNED NOT NULL',
                'chapter' => 'tinyint unsigned NOT NULL',
                'verse' => 'tinyint unsigned NOT NULL',
                'subverse' => 'char(3) NOT NULL',
                'order_by' => 'mediumint unsigned NOT NULL DEFAULT 0',
                'text' => 'MEDIUMTEXT NOT NULL',
                'paragraph_break' => 'tinyint unsigned NOT NULL DEFAULT 0'
            ),
            'xf_bible_book' => array(
                'book_id' => 'int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'url_portion' => 'varchar(50) NOT NULL',
                'priority' => 'int unsigned NOT NULL DEFAULT 0',
                'section' => 'ENUM(\'O\', \'N\', \'A\', \'\') NOT NULL DEFAULT \'\''
            ),
            'xf_bible_book_name' => array(
                'book_name' => 'varchar(50) NOT NULL PRIMARY KEY',
                'book_id' => 'int UNSIGNED NOT NULL'
            ),
            'xf_post_bible_verse' => array(
                'post_id' => 'int UNSIGNED NOT NULL',
                'book_id' => 'int UNSIGNED NOT NULL',
                'chapter' => 'tinyint unsigned NOT NULL',
                'verse' => 'tinyint unsigned NOT NULL DEFAULT 0',
                'verse_to' => 'tinyint unsigned NOT NULL DEFAULT 0',
                'subverse' => 'char(3) NOT NULL DEFAULT \'\''
            )
        );
    }

    protected function _getUniqueKeys()
    {
        return array(
            'xf_bible_verse' => array(
                'bible_id_book_id_chapter_verse_subverse' => array(
                    'bible_id',
                    'book_id',
                    'chapter',
                    'verse',
                    'subverse'
                )
            ),
            'xf_bible_book' => array(
                'url_portion' => array(
                    'url_portion'
                )
            ),
            'xf_post_bible_verse' => array(
                'post_id_book_id_chapter_verse_verse_to_subverse' => array(
                    'post_id',
                    'book_id',
                    'chapter',
                    'verse',
                    'verse_to',
                    'subverse'
                )
            )
        );
    }

    protected function _getKeys()
    {
        return array(
            'xf_bible_verse' => array(
                'bible_id' => array(
                    'bible_id'
                ),
                'book_id' => array(
                    'book_id'
                ),
                'book_id_chapter' => array(
                    'book_id',
                    'chapter'
                ),
                'book_id_chapter_verse' => array(
                    'book_id',
                    'chapter',
                    'verse'
                )
            ),
            'xf_bible_book_name' => array(
                'book_id' => array(
                    'book_id'
                )
            ),
            'xf_post_bible_verse' => array(
                'post_id' => array(
                    'post_id'
                )
            ),
            'xf_post_bible_verse' => array(
                'book_id_chapter_verse' => array(
                    'book_id',
                    'chapter',
                    'verse'
                )
            ),
            'xf_post_bible_verse' => array(
                'book_id_chapter_verse_to' => array(
                    'book_id',
                    'chapter',
                    'verse_to'
                )
            ),
            'xf_post_bible_verse' => array(
                'book_id_chapter' => array(
                    'book_id',
                    'chapter'
                )
            )
        );
    }

    protected function _getContentTypes()
    {
        return array(
            'bible_verse' => array(
                'addon_id' => 'ThemeHouse_Bible',
                'fields' => array(
                    'search_handler_class' => 'ThemeHouse_Bible_Search_DataHandler_Verse',
                    'sitemap_handler_class' => 'ThemeHouse_Bible_SitemapHandler_Verse'
                )
            )
        );
    }
}