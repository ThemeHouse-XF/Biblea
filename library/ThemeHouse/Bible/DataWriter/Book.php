<?php

class ThemeHouse_Bible_DataWriter_Book extends XenForo_DataWriter
{

    /**
     * Constant for extra data that holds the value for the phrase
     * that is the title of this book.
     *
     * This value is required on inserts.
     *
     * @var string
     */
    const DATA_TITLE = 'phraseTitle';

    /**
     * Constant for extra data that holds the value for the allowed book names
     * for this book.
     *
     * This value is required on inserts.
     *
     * @var string
     */
    const DATA_BOOK_NAMES = 'bookNames';

    /**
     * Gets the fields that are defined for the table.
     * See parent for explanation.
     *
     * @return array
     */
    protected function _getFields()
    {
        return array(
            'xf_bible_book' => array(
                'book_id' => array(
                    'type' => self::TYPE_UINT,
                    'autoIncrement' => true
                ),
                'url_portion' => array(
                    'type' => self::TYPE_STRING,
                    'required' => true
                ),
                'priority' => array(
                    'type' => self::TYPE_UINT,
                    'default' => 0
                ),
                'section' => array(
                    'type' => self::TYPE_STRING,
                    'allowedValues' => array(
                        'O',
                        'N',
                        'A',
                        ''
                    ),
                    'default' => ''
                )
            )
        );
    }

    /**
     * Gets the actual existing data out of data that was passed in.
     * See parent for explanation.
     *
     * @param mixed
     *
     * @return array|false
     */
    protected function _getExistingData($data)
    {
        if (!$bookId = $this->_getExistingPrimaryKey($data, 'book_id')) {
            return false;
        }
        
        $book = $this->_getBookModel()->getBookById($bookId);
        if (!$book) {
            return false;
        }
        
        return $this->getTablesDataFromArray($book);
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return 'book_id = ' . $this->_db->quote($this->getExisting('book_id'));
    }

    /**
     * Pre-save handling.
     */
    protected function _preSave()
    {
        $titlePhrase = $this->getExtraData(self::DATA_TITLE);
        if ($titlePhrase !== null && strlen($titlePhrase) == 0) {
            $this->error(new XenForo_Phrase('please_enter_valid_title'), 'title');
        }
        
        $bookNames = $this->getExtraData(self::DATA_BOOK_NAMES);
        if ($bookNames !== null && strlen($bookNames) == 0) {
            $this->error(new XenForo_Phrase('th_please_enter_at_least_one_book_name_bible'), 'book_names');
        }
    }

    protected function _postSave()
    {
        if ($this->isUpdate() && $this->isChanged('url_portion')) {
            $this->_deleteMasterPhrase($this->_getTitlePhraseName($this->get('url_portion')));
        }
        
        $titlePhrase = $this->getExtraData(self::DATA_TITLE);
        if ($titlePhrase !== null) {
            $this->_insertOrUpdateMasterPhrase($this->_getTitlePhraseName($this->get('url_portion')), $titlePhrase, '', 
                array(
                    'global_cache' => true
                ));
        }
        
        $bookNames = explode(',', $this->getExtraData(self::DATA_BOOK_NAMES));
        foreach ($bookNames as $key => $bookName) {
            $bookNames[$key] = strtolower(trim($bookName));
        }
        array_unique($bookNames);
        $values = array();
        foreach ($bookNames as $bookName) {
            $values[] = '(' . $this->_db->quote($bookName) . ',' . $this->get('book_id') . ')';
        }
        if ($values) {
            $this->_db->query(
                '
                INSERT INTO xf_bible_book_name
                (book_name, book_id)
                VALUES ' . implode(',', $values) . '
                ON DUPLICATE KEY UPDATE book_id = book_id
            ');
        }
        
        $this->_getBookModel()->rebuildBookCache();
    }

    protected function _postDelete()
    {
        $this->_deleteMasterPhrase($this->_getTitlePhraseName($this->get('url_portion')));
        
        $bookId = $this->get('book_id');
        
        $this->_db->delete('xf_bible_book_name', 'book_id = ' . $this->_db->quote($bookId));
        
        $this->_getBookModel()->rebuildBookCache();
        
        XenForo_Application::defer('ThemeHouse_Bible_Deferred_VerseDelete',
            array(
                'book_id' => $bookId
            ), "verseDelete_$bookId", true);
    }

    /**
     * Gets the name of the book's title phrase.
     *
     * @param string $urlPortion
     *
     * @return string
     */
    protected function _getTitlePhraseName($urlPortion)
    {
        return $this->_getBookModel()->getBookPhraseTitle($urlPortion);
    }

    /**
     *
     * @return ThemeHouse_Bible_Model_Book
     */
    protected function _getBookModel()
    {
        return $this->getModelFromCache('ThemeHouse_Bible_Model_Book');
    }
}