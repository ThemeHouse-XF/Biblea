<?php

class ThemeHouse_Bible_DataWriter_Verse extends XenForo_DataWriter
{

    public static $bibleCache = array();

    public static $bookCache = array();

    const DATA_BIBLE = 'bibleInfo';

    const DATA_BOOK = 'bookInfo';

    /**
     * Gets the fields that are defined for the table.
     * See parent for explanation.
     *
     * @return array
     */
    protected function _getFields()
    {
        return array(
            'xf_bible_verse' => array(
                'verse_id' => array(
                    'type' => self::TYPE_UINT,
                    'autoIncrement' => true
                ),
                'bible_id' => array(
                    'type' => self::TYPE_STRING,
                    'required' => true
                ),
                'book_id' => array(
                    'type' => self::TYPE_UINT,
                    'required' => true
                ),
                'chapter' => array(
                    'type' => self::TYPE_UINT,
                    'required' => true
                ),
                'verse' => array(
                    'type' => self::TYPE_UINT,
                    'required' => true
                ),
                'subverse' => array(
                    'type' => self::TYPE_STRING,
                    'default' => ''
                ),
                'text' => array(
                    'type' => self::TYPE_STRING,
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
        if (!$verseId = $this->_getExistingPrimaryKey($data, 'verse_id')) {
            return false;
        }
        
        $verse = $this->_getVerseModel()->getVerseById($verseId);
        if (!$verse) {
            return false;
        }
        
        return $this->getTablesDataFromArray($verse);
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return 'verse_id = ' . $this->_db->quote($this->getExisting('verse_id'));
    }

    protected function _postSave()
    {
        $bible = $this->_getBibleData();
        $book = $this->_getBookData();
        
        $verse = array_merge($bible, $book, $this->getMergedData());
        
        $dataHandler = XenForo_Search_DataHandler_Abstract::create('ThemeHouse_Bible_Search_DataHandler_Verse');
        $indexer = new XenForo_Search_Indexer();
        $dataHandler->insertIntoIndex($indexer, $verse);
    }

    protected function _postDelete()
    {
        $dataHandler = XenForo_Search_DataHandler_Abstract::create('ThemeHouse_Bible_Search_DataHandler_Verse');
        $indexer = new XenForo_Search_Indexer();
        $dataHandler->deleteFromIndex($indexer, $this->getMergedData());
    }

    /**
     *
     * @see XenForo_DataWriter::setExtraData
     */
    public function setExtraData($name, $value)
    {
        if ($name == self::DATA_BIBLE && is_array($value) && !empty($value['bible_id'])) {
            self::setBibleCacheItem($value);
        }
        
        if ($name == self::DATA_BOOK && is_array($value) && !empty($value['book_id'])) {
            self::setBookCacheItem($value);
        }
        
        return parent::setExtraData($name, $value);
    }

    /**
     * Get the data for the Bible the verse is in
     *
     * @return array
     */
    protected function _getBibleData()
    {
        if (!$bible = $this->getExtraData(self::DATA_BIBLE)) {
            $bible = self::getBibleCacheItem($this->get('bible_id'));
        }
        
        return $bible;
    }

    /**
     * Get the data for the book the verse is in
     *
     * @return array
     */
    protected function _getBookData()
    {
        if (!$book = $this->getExtraData(self::DATA_BOOK)) {
            $book = self::getBookCacheItem($this->get('book_id'));
        }
        
        return $book;
    }

    public static function setBibleCacheItem(array $bible)
    {
        self::$bibleCache[$bible['bible_id']] = $bible;
    }

    public static function getBibleCacheItem($bibleId)
    {
        if (!self::isBibleCacheItem($bibleId)) {
            $bible = XenForo_Model::create('ThemeHouse_Bible_Model_Bible')->getBibleById($bibleId);
            if (!$bible) {
                self::$bibleCache[$bibleId] = false;
            } else {
                self::setBibleCacheItem($bible);
            }
        }
        
        return self::$bibleCache[$bibleId];
    }

    public static function isBibleCacheItem($bibleId)
    {
        return array_key_exists($bibleId, self::$bibleCache);
    }

    public static function setBookCacheItem(array $book)
    {
        self::$bookCache[$book['book_id']] = $book;
    }

    public static function getBookCacheItem($bookId)
    {
        if (!self::isBookCacheItem($bookId)) {
            $book = XenForo_Model::create('ThemeHouse_Bible_Model_Book')->getBookById($bookId);
            if (!$book) {
                self::$bookCache[$bookId] = false;
            } else {
                self::setBookCacheItem($book);
            }
        }
        
        return self::$bookCache[$bookId];
    }

    public static function isBookCacheItem($bookId)
    {
        return array_key_exists($bookId, self::$bookCache);
    }

    /**
     *
     * @return ThemeHouse_Bible_Model_Verse
     */
    protected function _getVerseModel()
    {
        return $this->getModelFromCache('ThemeHouse_Bible_Model_Verse');
    }
}