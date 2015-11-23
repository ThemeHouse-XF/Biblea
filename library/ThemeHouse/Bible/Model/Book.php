<?php

class ThemeHouse_Bible_Model_Book extends XenForo_Model
{

    public function getBookByName($bookName)
    {
        return $this->_getDb()->fetchRow(
            '
            SELECT book_name.*, book.*
            FROM xf_bible_book_name AS book_name
            INNER JOIN xf_bible_book AS book
                ON (book_name.book_id = book.book_id)
            WHERE book_name = ?
        ', $bookName);
    }

    public function getBookByUrlPortion($urlPortion)
    {
        return $this->_getDb()->fetchRow(
            '
            SELECT *
            FROM xf_bible_book
            WHERE url_portion = ?
        ', $urlPortion);
    }

    public function getBookById($bookId)
    {
        return $this->_getDb()->fetchRow(
            '
            SELECT *
            FROM xf_bible_book
            WHERE book_id = ?
        ', $bookId);
    }

    public function getBooksForBible($bibleId)
    {
        return $this->fetchAllKeyed(
            '
                SELECT DISTINCT(book.book_id), book.*
                FROM xf_bible_book AS book
                INNER JOIN xf_bible_verse AS verse
                    ON (book.book_id = verse.book_id)
                WHERE verse.bible_id = ?
                ORDER BY FIELD(book.section, \'O\', \'N\', \'A\', \'\'),
                    book.priority ASC
        ', 'book_id', $bibleId);
    }

    public function getSectionsForBible($bibleId)
    {
        return $this->_getDb()->fetchCol(
            '
            SELECT DISTINCT(book.section)
            FROM xf_bible_book AS book
            INNER JOIN xf_bible_verse AS verse
                ON (book.book_id = verse.book_id)
            WHERE bible_id = ?
        ', $bibleId);
    }

    public function getSectionTitles()
    {
        return array(
            'O' => new XenForo_Phrase('th_old_testament_bible'),
            'N' => new XenForo_Phrase('th_new_testament_bible'),
            'A' => new XenForo_Phrase('th_deuterocanon_apocrypha_bible')
        );
    }

    public function getSectionUrlPortions()
    {
        return array(
            'O' => 'old-testament',
            'N' => 'new-testament',
            'A' => 'apocrypha'
        );
    }

    public function getBooksBySection($section, $bibleId)
    {
        return $this->fetchAllKeyed(
            '
            SELECT DISTINCT(book.book_id), book.*
            FROM xf_bible_book AS book
            INNER JOIN xf_bible_verse AS verse
                ON (book.book_id = verse.book_id)
            WHERE book.section = ? AND verse.bible_id = ?
        ', 'book_id', array(
                $section,
                $bibleId
            ));
    }

    public function prepareBooks(array $books)
    {
        foreach ($books as &$book) {
            $book = $this->prepareBook($book);
        }
        
        return $books;
    }

    public function prepareBook(array $book)
    {
        $titlePhrase = $this->_getVerseModel()->getBookPhraseTitle($book['url_portion']);
        
        $book['title'] = new XenForo_Phrase($titlePhrase);
        
        return $book;
    }

    public function countChaptersInBook($bookId, $bibleId)
    {
        return $this->_getDb()->fetchOne(
            '
                SELECT COUNT(DISTINCT(verse.chapter))
                FROM xf_bible_verse AS verse
                WHERE verse.book_id = ?
                    AND verse.bible_id = ?
            ', array(
                $bookId,
                $bibleId
            ));
    }

    /**
     *
     * @return ThemeHouse_Bible_Model_Verse
     */
    protected function _getVerseModel()
    {
        return $this->getModelFromCache('ThemeHouse_Bible_Model_Verse');
    }

    public function getBooks(array $conditions = array(), array $fetchOptions = array())
    {
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
        
        return $this->fetchAllKeyed(
            $this->limitQueryResults('
                SELECT *
                FROM xf_bible_book
            ', $limitOptions['limit'], $limitOptions['offset']), 
            'book_id');
    }

    public function getBookNames(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->prepareBookNameConditions($conditions, $fetchOptions);
        
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
        
        return $this->_getDb()->fetchPairs(
            $this->limitQueryResults(
                '
                    SELECT book_name, book_id
                    FROM xf_bible_book_name AS book_name
			        WHERE ' . $whereConditions . '
                ', $limitOptions['limit'], $limitOptions['offset']));
    }

    public function prepareBookNameConditions(array $conditions, array &$fetchOptions)
    {
        $sqlConditions = array();
        $db = $this->_getDb();
        
        if (!empty($conditions['book_id'])) {
            $sqlConditions[] = 'book_name.book_id = ' . $db->quote($conditions['book_id']);
        }
        
        return $this->getConditionsForClause($sqlConditions);
    }

    public function getChapterVerseCounts()
    {
        return $this->_getDb()->fetchAll(
            '
                SELECT MAX(verse_count) AS verse_count, book_id, chapter
                FROM (SELECT COUNT(*) AS verse_count, book_id, chapter, bible_id
                    FROM xf_bible_verse
                    GROUP BY book_id, chapter, bible_id) AS verse_counts
                GROUP BY book_id, chapter
            ');
    }

    public function rebuildBookCache(array $books = null, array $bookNames = null)
    {
        $cache = array();
        
        $chapterVerseCounts = $this->getChapterVerseCounts();
        
        foreach ($chapterVerseCounts as $chapterVerseCount) {
            $cache[$chapterVerseCount['book_id']]['verse_count'][$chapterVerseCount['chapter']] = $chapterVerseCount['verse_count'];
        }
        
        if ($books === null) {
            $books = $this->getBooks();
        }
        
        foreach ($books as $bookId => $book) {
            if (isset($cache[$bookId])) {
                $cache[$bookId]['url_portion'] = $book['url_portion'];
            }
        }
        
        if ($bookNames === null) {
            $bookNames = $this->getBookNames();
        }
        
        foreach ($bookNames as $bookName => $bookId) {
            if (isset($cache[$bookId])) {
                $bookName = strtolower($bookName);
                if (!isset($cache[$bookId]['book_names']) || !in_array($bookName, $cache[$bookId]['book_names'])) {
                    $cache[$bookId]['book_names'][] = preg_quote($bookName);
                }
            }
        }
        
        XenForo_Application::setSimpleCacheData('th_bible_bookCache', $cache);
        
        return $cache;
    }

    public function getBookCache()
    {
        $cache = XenForo_Application::getSimpleCacheData('th_bible_bookCache');
        
        if ($cache === false) {
            $cache = $this->rebuildBookCache();
        }
        
        return $cache;
    }

    public function getBookPhraseTitle($urlPortion)
    {
        return 'th_book_' . str_replace('-', '_', $urlPortion) . '_bible';
    }

    /**
     * Gets a book's master title phrase text.
     *
     * @param integer $urlPortion
     *
     * @return string
     */
    public function getBookMasterTitlePhraseValue($urlPortion)
    {
        $phraseName = $this->getBookPhraseTitle($urlPortion);
        return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
    }
    
    /**
     * @return XenForo_Model_Phrase
     */
    protected function _getPhraseModel()
    {
        return $this->getModelFromCache('XenForo_Model_Phrase');
    }
}