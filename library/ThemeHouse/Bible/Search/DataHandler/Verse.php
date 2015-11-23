<?php

/**
 * Handles searching of verses.
 *
 * @package XenForo_Search
 */
class ThemeHouse_Bible_Search_DataHandler_Verse extends XenForo_Search_DataHandler_Abstract
{

    /**
     *
     * @var ThemeHouse_Bible_Model_Verse
     */
    protected $_verseModel = null;

    /**
     * Inserts into (or replaces a record) in the index.
     *
     * @see XenForo_Search_DataHandler_Abstract::_insertIntoIndex()
     */
    protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
    {
        $metadata = array(
            'book_id' => $data['book_id'],
            'chapter' => $data['chapter']
        );
        
        if (!isset($data['last_modified'])) {
            $data['last_modified'] = XenForo_Application::$time;
        }
        
        $title = isset($data['verse_title']) ? $data['verse_title'] : '';
        
        $indexer->insertIntoIndex('bible_verse', $data['verse_id'], $title, $data['text'], $data['last_modified'], 0, 
            $data['bible_id'], $metadata);
    }

    /**
     * Updates a record in the index.
     *
     * @see XenForo_Search_DataHandler_Abstract::_updateIndex()
     */
    protected function _updateIndex(XenForo_Search_Indexer $indexer, array $data, array $fieldUpdates)
    {
        $indexer->updateIndex('bible_verse', $data['verse_id'], $fieldUpdates);
    }

    /**
     * Deletes one or more records from the index.
     *
     * @see XenForo_Search_DataHandler_Abstract::_deleteFromIndex()
     */
    protected function _deleteFromIndex(XenForo_Search_Indexer $indexer, array $dataList)
    {
        $verseIds = array();
        foreach ($dataList as $data) {
            $verseIds[] = is_array($data) ? $data['verse_id'] : $data;
        }
        
        $indexer->deleteFromIndex('bible_verse', $verseIds);
    }

    /**
     * Rebuilds the index for a batch.
     *
     * @see XenForo_Search_DataHandler_Abstract::rebuildIndex()
     */
    public function rebuildIndex(XenForo_Search_Indexer $indexer, $lastId, $batchSize)
    {
        $verseIds = $this->_getVerseModel()->getVerseIdsInRange($lastId, $batchSize);
        if (!$verseIds) {
            return false;
        }
        
        $this->quickIndex($indexer, $verseIds);
        
        return max($verseIds);
    }

    /**
     * Rebuilds the index for the specified content.
     *
     * @see XenForo_Search_DataHandler_Abstract::quickIndex()
     */
    public function quickIndex(XenForo_Search_Indexer $indexer, array $contentIds)
    {
        $verseModel = $this->_getVerseModel();
        
        $verses = $verseModel->getVersesByIds($contentIds, array(
            'join' => ThemeHouse_Bible_Model_Verse::FETCH_BIBLE | ThemeHouse_Bible_Model_Verse::FETCH_BIBLE_BOOK
        ));
        
        $verses = $verseModel->prepareVerses($verses);
        
        foreach ($verses as $verse) {
            $this->insertIntoIndex($indexer, $verse);
        }
        
        return true;
    }

    /**
     * Gets the type-specific data for a collection of results of this content
     * type.
     *
     * @see XenForo_Search_DataHandler_Abstract::getDataForResults()
     */
    public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
    {
        $verseModel = $this->_getVerseModel();
        
        $verses = $verseModel->getVersesByIds($ids, 
            array(
                'join' => ThemeHouse_Bible_Model_Verse::FETCH_BIBLE | ThemeHouse_Bible_Model_Verse::FETCH_BIBLE_BOOK
            ));
        
        return $verses;
    }

    /**
     * Determines if this result is viewable.
     *
     * @see XenForo_Search_DataHandler_Abstract::canViewResult()
     */
    public function canViewResult(array $result, array $viewingUser)
    {
        return true;
    }

    /**
     * Prepares a result for display.
     *
     * @see XenForo_Search_DataHandler_Abstract::prepareResult()
     */
    public function prepareResult(array $result, array $viewingUser)
    {
        $verseModel = $this->_getVerseModel();
        
        return $this->_getVerseModel()->prepareVerse($result);
    }

    /**
     * Gets the date of the result (from the result's content).
     *
     * @see XenForo_Search_DataHandler_Abstract::getResultDate()
     */
    public function getResultDate(array $result)
    {
        return $result['last_modified'];
    }

    /**
     * Renders a result to HTML.
     *
     * @see XenForo_Search_DataHandler_Abstract::renderResult()
     */
    public function renderResult(XenForo_View $view, array $result, array $search)
    {
        return $view->createTemplateObject('th_search_result_bible_verse_bible',
            array(
                'verse' => $result,
                'search' => $search
            ));
    }

    /**
     * Returns an array of content types handled by this class
     *
     * @see XenForo_Search_DataHandler_Abstract::getSearchContentTypes()
     */
    public function getSearchContentTypes()
    {
        return array(
            'bible_verse'
        );
    }
    
    /**
     * Gets the phrase for this search content type.
     *
     * @return XenForo_Phrase
     */
    public function getSearchContentTypePhrase()
    {
        return new XenForo_Phrase('th_bible_verse_bible');
    }

    /**
     * Gets the search form controller response for this type.
     *
     * @see XenForo_Search_DataHandler_Abstract::getSearchFormControllerResponse()
     */
    public function getSearchFormControllerResponse(XenForo_ControllerPublic_Abstract $controller, XenForo_Input $input, 
        array $viewParams)
    {
        return $controller->responseView('ThemeHouse_Bible_ViewPublic_Search_Form_Verse',
            'th_search_form_bible_verse_bible', $viewParams);
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
}