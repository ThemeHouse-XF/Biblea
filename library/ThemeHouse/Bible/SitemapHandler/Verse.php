<?php

class ThemeHouse_Bible_SitemapHandler_Verse extends XenForo_SitemapHandler_Abstract
{

    protected $_verseModel;

    public function getRecords($previousLast, $limit, array $viewingUser)
    {
        $verseModel = $this->_getVerseModel();
        $ids = $verseModel->getVerseIdsInRange($previousLast, $limit);
        
        return $verseModel->getVersesByIds($ids, 
            array(
                'join' => ThemeHouse_Bible_Model_Verse::FETCH_BIBLE
            ));
    }

    public function isIncluded(array $entry, array $viewingUser)
    {
        if ($entry['verse'] != 1) {
            return false;
        }
        
        return true;
    }

    public function getData(array $entry)
    {
        $verseModel = $this->_getVerseModel();
        
        return array(
            'loc' => XenForo_Link::buildPublicLink('canonical:bible', $entry),
            'lastmod' => $entry['last_modified']
        );
    }

    public function isInterruptable()
    {
        return true;
    }

    /**
     *
     * @return ThemeHouse_Bible_Model_Verse
     */
    protected function _getVerseModel()
    {
        if (!$this->_verseModel) {
            $this->_verseModel = XenForo_Model::create('ThemeHouse_Bible_Model_Verse');
        }
        
        return $this->_verseModel;
    }

    public function getPhraseKey($key)
    {
        return 'th_bible_verses_bible';
    }
}