<?php

class ThemeHouse_Bible_Model_Verse extends XenForo_Model
{

    const FETCH_BIBLE = 0x01;

    const FETCH_BIBLE_BOOK = 0x02;

    const FETCH_PARAGRAPH_BREAK = 0x04;

    public function getVerseById($verseId)
    {
        return $this->_getDb()->fetchRow(
            '
            SELECT *
            FROM xf_bible_verse
            WHERE verse_id = ?
        ', $verseId);
    }

    public function getSpecificVerse($bibleId, $bookId, $chapter, $verse)
    {
        return $this->_getDb()->fetchRow(
            '
            SELECT *
            FROM xf_bible_verse
            WHERE bible_id = ? AND book_id = ? AND chapter = ?
                AND verse = ?
        ', 
            array(
                $bibleId,
                $bookId,
                $chapter,
                $verse
            ));
    }

    public function parseVerse($text, $allowNoVerse = false)
    {
        if (preg_match('#^(.*) ([0-9]+)(?::([0-9]+)(?:-([0-9]+))?)?$#', $text, $matches)) {
            $bookName = $matches[1];
            $chapter = $matches[2];
            $verse = isset($matches[3]) ? $matches[3] : 0;
            $verseTo = isset($matches[4]) ? $matches[4] : 0;
            
            if (!$verse && !$allowNoVerse) {
                return false;
            }
            
            /* @var $bookModel ThemeHouse_Bible_Model_Book */
            $bookModel = XenForo_Model::create('ThemeHouse_Bible_Model_Book');
            
            $bookCache = $bookModel->getBookCache();
            
            $bookId = null;
            $urlPortion = null;
            foreach ($bookCache as $_bookId => $book) {
                if (isset($book['book_names']) && in_array(strtolower($bookName), $book['book_names'])) {
                    if (!isset($book['verse_count'][$chapter])) {
                        return false;
                    }
                    if ($verse > $book['verse_count'][$chapter]) {
                        return false;
                    }
                    if ($verseTo > $book['verse_count'][$chapter]) {
                        $verseTo = $book['verse_count'][$chapter];
                        if ($verseTo == $verse) {
                            $verseTo = 0;
                        }
                    }
                    $bookId = $_bookId;
                    $urlPortion = $book['url_portion'];
                    break;
                }
            }
            
            if (!$bookId) {
                return false;
            }
            
            $text = $bookName . ' ' . $chapter;
            if ($verse) {
                $text .= ':' . $verse;
                if ($verseTo) {
                    $text .= '-' . $verseTo;
                }
            }
            
            return array(
                'book_id' => $bookId,
                'url_portion' => $urlPortion,
                'chapter' => $chapter,
                'verse' => $verse,
                'verse_to' => $verseTo,
                'text' => $text
            );
        }
        
        return false;
    }

    public function getVerseFromText($text, &$bibleId = null, $bbCode = false)
    {
        $verse = '';
        
        if ($bibleId === null) {
            $xenOptions = XenForo_Application::get('options');
            $bibleId = $xenOptions->th_bible_defaultBible;
        }
        
        $parsedVerse = $this->parseVerse($text);
        
        if ($parsedVerse) {
            $bookId = $parsedVerse['book_id'];
            $chapter = $parsedVerse['chapter'];
            $verse = $parsedVerse['verse'];
            $verseTo = $parsedVerse['verse_to'];
            
            return $this->getFormattedVerse($bibleId, $bookId, $chapter, $verse, $verseTo, $bbCode);
        }
        
        if ($bbCode) {
            return array();
        } else {
            return '';
        }
    }

    public function getFormattedVerse($bibleId, $bookId, $chapter, $verse, $verseTo, $bbCode = false, $lineBreak = "\n\n")
    {
        if ($verse && !$verseTo) {
            $verse = $this->getSpecificVerse($bibleId, $bookId, $chapter, $verse);
            if ($bbCode) {
                return array(
                    $verse['text']
                );
            } else {
                return $verse['text'];
            }
        } else {
            $conditions = array(
                'bible_id' => $bibleId,
                'book_id' => $bookId,
                'chapter' => $chapter
            );
            if ($verse && $verseTo) {
                $conditions['verses'] = array(
                    $verse,
                    $verseTo
                );
            }
            $verses = $this->getVerses($conditions);
            $verses = $this->prepareVerses($verses, array());
            
            $i = 0;
            if ($bbCode) {
                $verseBbCode = array();
                foreach ($verses as $_verse) {
                    $i++;
                    $verseBbCode[] = array(
                        'tag' => 'versenum',
                        'option' => '',
                        'original' => array(
                            '[VERSENUM]',
                            '[/VERSENUM]'
                        ),
                        'children' => array(
                            $_verse['verse']
                        )
                    );
                    $verseBbCode[] = $_verse['text'] . ' ' .
                         ($i != count($verses) && $_verse['paragraph_break'] ? $lineBreak : ' ');
                }
                return $verseBbCode;
            } else {
                $verse = '';
                foreach ($verses as $_verse) {
                    $i++;
                    $verse .= '[VERSENUM]' . $_verse['verse'] . '[/VERSENUM] ' . $_verse['text'] .
                         ($i != count($verses) && $_verse['paragraph_break'] ? $lineBreak : ' ');
                }
                return trim($verse);
            }
        }
        
        if ($bbCode) {
            return array();
        } else {
            return '';
        }
    }

    public function getVerses(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->prepareVerseConditions($conditions, $fetchOptions);
        
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
        $joinOptions = $this->prepareVerseJoinOptions($fetchOptions);
        
        return $this->fetchAllKeyed(
            $this->limitQueryResults(
                '
                    SELECT verse.*
                    ' . $joinOptions['selectFields'] . '
                    FROM xf_bible_verse AS verse
                    ' . $joinOptions['joinTables'] . '
        			WHERE ' . $whereConditions . '
                ', $limitOptions['limit'], $limitOptions['offset']), 
            'verse');
    }

    public function getVerseIds(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->prepareVerseConditions($conditions, $fetchOptions);
        
        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
        $joinOptions = $this->prepareVerseJoinOptions($fetchOptions);
        
        return $this->_getDb()->fetchCol(
            $this->limitQueryResults(
                '
                    SELECT verse.verse_id
                    ' . $joinOptions['selectFields'] . '
                    FROM xf_bible_verse AS verse
                    ' . $joinOptions['joinTables'] . '
        			WHERE ' . $whereConditions . '
                ', $limitOptions['limit'], $limitOptions['offset']));
    }

    /**
     *
     * @param array $verseIds
     * @param array $fetchOptions
     *
     * @return array
     */
    public function getVersesByIds(array $verseIds, array $fetchOptions = array())
    {
        if (!$verseIds) {
            return array();
        }
        
        $joinOptions = $this->prepareVerseJoinOptions($fetchOptions);
        
        return $this->fetchAllKeyed(
            '
            SELECT verse.*
                ' . $joinOptions['selectFields'] . '
            FROM xf_bible_verse AS verse
            ' . $joinOptions['joinTables'] . '
            WHERE verse.verse_id IN (' . $this->_getDb()
                ->quote($verseIds) . ')
        ', 'verse_id');
    }

    /**
     * Gets verse IDs in the specified range.
     * The IDs returned will be those immediately
     * after the "start" value (not including the start), up to the specified
     * limit.
     *
     * @param integer $start IDs greater than this will be returned
     * @param integer $limit Number of posts to return
     *
     * @return array List of IDs
     */
    public function getVerseIdsInRange($start, $limit)
    {
        $db = $this->_getDb();
        
        return $db->fetchCol(
            $db->limit('
			SELECT verse_id
			FROM xf_bible_verse
			WHERE verse_id > ?
			ORDER BY verse_id
		', $limit), $start);
    }

    public function countVerses(array $conditions = array())
    {
        $fetchOptions = array();
        $whereConditions = $this->prepareVerseConditions($conditions, $fetchOptions);
        
        $joinOptions = $this->prepareVerseJoinOptions($fetchOptions);
        
        return $this->_getDb()->fetchOne(
            '
			SELECT COUNT(*)
			FROM xf_bible_verse AS verse
            ' . $joinOptions['joinTables'] . '
			WHERE ' . $whereConditions . '
		');
    }

    public function getPostIdsForVerse($bookId, $chapter, $verse = null, $verseTo = null)
    {
        $db = $this->_getDb();
        
        $verseQuoted = $db->quote($verse);
        $verseToQuoted = $db->quote($verseTo);
        
        return $db->fetchCol(
            '
                SELECT post_id
                FROM xf_post_bible_verse
                WHERE book_id = ? AND chapter = ?
                    ' .
                 ($verse && $verseTo ? 'AND verse = ' . $verseQuoted . ' AND verse_to = ' . $verseToQuoted : '') . '
                    ' . ($verse &&
                 !$verseTo ? 'AND ((verse = ' . $verseQuoted . ' AND verse_to = 0) OR (verse <= ' . $verseQuoted .
                 ' AND verse_to >= ' . $verseQuoted . '))' : '') . '
                ORDER BY post_id DESC
            ', array(
                    $bookId,
                    $chapter
                ));
    }

    /**
     * Prepares verse join options.
     *
     * @param array $fetchOptions
     *
     * @return array
     */
    public function prepareVerseJoinOptions(array $fetchOptions)
    {
        $selectFields = '';
        $joinTables = '';
        
        $db = $this->_getDb();
        
        if (!empty($fetchOptions['join'])) {
            if ($fetchOptions['join'] & self::FETCH_BIBLE) {
                $selectFields .= ',
                    bible.*';
                $joinTables .= '
                    LEFT JOIN xf_bible AS bible ON
                        (verse.bible_id = bible.bible_id)';
            }
            if ($fetchOptions['join'] & self::FETCH_BIBLE_BOOK) {
                $selectFields .= ',
                    book.*';
                $joinTables .= '
                    LEFT JOIN xf_bible_book AS book ON
                        (verse.book_id = book.book_id)';
            }
        }
        
        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables
        );
    }

    public function prepareVerseConditions(array $conditions, array &$fetchOptions)
    {
        $sqlConditions = array();
        $db = $this->_getDb();
        
        if (!empty($conditions['bible_id'])) {
            $sqlConditions[] = 'verse.bible_id = ' . $db->quote($conditions['bible_id']);
        }
        
        if (!empty($conditions['chapter'])) {
            $sqlConditions[] = 'verse.chapter = ' . $db->quote($conditions['chapter']);
        }
        
        if (!empty($conditions['verse'])) {
            $sqlConditions[] = 'verse.verse = ' . $db->quote($conditions['verse']);
        }
        
        if (!empty($conditions['verses'])) {
            $sqlConditions[] = 'verse.verse >= ' . $db->quote($conditions['verses'][0]) . ' AND verse.verse <= ' .
                 $db->quote($conditions['verses'][1]);
        }
        
        if (!empty($conditions['url_portion'])) {
            $this->addFetchOptionJoin($fetchOptions, self::FETCH_BIBLE_BOOK);
            $sqlConditions[] = 'book.url_portion = ' . $db->quote($conditions['url_portion']);
        }
        
        if (!empty($conditions['book_id'])) {
            $sqlConditions[] = 'verse.book_id = ' . $db->quote($conditions['book_id']);
        }
        
        return $this->getConditionsForClause($sqlConditions);
    }

    public function getBookPhraseTitle($urlPortion)
    {
        return $this->_getBookModel()->getBookPhraseTitle($urlPortion);
    }

    public function prepareVerse(array $verse)
    {
        if (!empty($verse['url_portion'])) {
            $verse['book_title'] = new XenForo_Phrase($this->getBookPhraseTitle($verse['url_portion']));
            
            $verse['verse_title'] = $verse['book_title'] . ' ' . $verse['chapter'] . ':' . $verse['verse'];
        }
        $verse['book_id'] = (int) $verse['book_id'];
        
        if (!empty($verse['section'])) {
            $sectionTitles = $this->_getBookModel()->getSectionTitles();
            $sectionUrlPortions = $this->_getBookModel()->getSectionUrlPortions();
            
            $verse['section_title'] = $sectionTitles[$verse['section']];
            $verse['section_url_portion'] = $sectionUrlPortions[$verse['section']];
        }
        
        $pe = mb_strpos($verse['text'], html_entity_decode('&#1508;', ENT_COMPAT, "UTF-8"), 0, "UTF-8");
        if ($pe !== false) {
            $verse['pe'] = 1;
        }
        
        $samekh = mb_strpos($verse['text'], html_entity_decode('&#1505;', ENT_COMPAT, "UTF-8"), 0, "UTF-8");
        if ($samekh !== false) {
            $verse['samekh'] = 1;
        }
        
        if (preg_match('#[!.?]["\']*$#', $verse['text'])) {
            $verse['paragraph_break'] = 1;
        }
        
        $verse['bible_title'] = new XenForo_Phrase($this->_getBibleModel()->getBibleTitlePhraseName($verse['bible_id']));
        
        return $verse;
    }

    public function prepareVerses(array $verses)
    {
        foreach ($verses as &$verse) {
            $verse = $this->prepareVerse($verse);
        }
        
        return $verses;
    }

    /**
     *
     * @return ThemeHouse_Bible_Model_Bible
     */
    protected function _getBibleModel()
    {
        return $this->getModelFromCache('ThemeHouse_Bible_Model_Bible');
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