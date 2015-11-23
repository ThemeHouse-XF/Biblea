<?php

class ThemeHouse_Bible_Model_Bible extends XenForo_Model
{

    const BIBLE_TEMPLATE_PREFIX = '_bible.';

    const FETCH_VERSE = 0x01;

    public function getBibleById($bibleId)
    {
        return $this->_getDb()->fetchRow(
            '
            SELECT *
            FROM xf_bible
            WHERE bible_id = ?
        ', $bibleId);
    }

    public function getBibles(array $conditions = array(), array $fetchOptions = array())
    {
        $whereConditions = $this->prepareBibleConditions($conditions, $fetchOptions);

        $limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
        $joinOptions = $this->prepareBibleJoinOptions($fetchOptions);

        return $this->fetchAllKeyed(
            $this->limitQueryResults('
            SELECT bible.*
            ' . $joinOptions['selectFields'] . '
            FROM xf_bible AS bible
            ' . $joinOptions['joinTables'] . '
			WHERE ' . $whereConditions . '
            ' . $joinOptions['groupBy'] . '
        ', $limitOptions['limit'], $limitOptions['offset']),
            'bible_id');
    }

    /**
     * Returns an array of all Bibles, suitable for use in ACP template syntax
     * as options source.
     *
     * @param string|array $selectedId
     * @param array $bibles
     *
     * @return array
     */
    public function getBiblesForOptionsTag($selectedId = null, $bibles = null)
    {
        if ($bibles === null) {
            $bibles = $this->getBibles();
        }

        $options = array();
        foreach ($bibles as $id => $bible) {
            $options[$id] = array(
                'value' => $id,
                'label' => $bible['name'],
                'selected' => is_array($selectedId) ? in_array($id, $selectedId) : ($selectedId == $id)
            );
        }

        return $options;
    }

    /**
     * Prepares bible join options.
     *
     * @param array $fetchOptions
     *
     * @return array
     */
    public function prepareBibleJoinOptions(array $fetchOptions)
    {
        $selectFields = '';
        $joinTables = '';
        $groupBy = '';

        $db = $this->_getDb();

        if (!empty($fetchOptions['join'])) {
            if ($fetchOptions['join'] & self::FETCH_VERSE) {
                $selectFields .= ',
                    verse.*';
                $joinTables .= '
                    LEFT JOIN xf_bible_verse AS verse ON
                        (verse.bible_id = bible.bible_id)';
                $groupBy = 'GROUP BY bible.bible_id';
            }
        }

        return array(
            'selectFields' => $selectFields,
            'joinTables' => $joinTables,
            'groupBy' => $groupBy
        );
    }

    public function prepareBibleConditions(array $conditions, array &$fetchOptions)
    {
        $sqlConditions = array();
        $db = $this->_getDb();

        if (!empty($conditions['book_id'])) {
            $sqlConditions[] = 'verse.book_id = ' . $db->quote($conditions['book_id']);
            $this->addFetchOptionJoin($fetchOptions, self::FETCH_VERSE);
        }

        if (!empty($conditions['chapter'])) {
            $sqlConditions[] = 'verse.chapter = ' . $db->quote($conditions['chapter']);
            $this->addFetchOptionJoin($fetchOptions, self::FETCH_VERSE);
        }

        return $this->getConditionsForClause($sqlConditions);
    }

    public function prepareBible(array $bible)
    {
        $bible['title'] = new XenForo_Phrase($this->getBibleTitlePhraseName($bible['bible_id']));

        return $bible;
    }

    public function prepareBibles(array $bibles)
    {
        foreach ($bibles as &$bible) {
            $bible = $this->prepareBible($bible);
        }

        return $bibles;
    }

    public function importBibleZip(XenForo_Upload $file)
    {
        $path = $file->getTempFile();

        $bibleId = pathinfo($file->getFileName(), PATHINFO_FILENAME);

        $internalData = XenForo_Helper_File::getInternalDataPath();

        XenForo_Helper_File::createDirectory($internalData . '/bibles/' . $bibleId);

        $zip = new ZipArchive();
        if (@$zip->open($path) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                copy("zip://" . $path . "#" . $filename, $internalData . '/bibles/' . $bibleId . '/' . $filename);
            }
            $zip->close();
        }

        XenForo_Application::defer('ThemeHouse_Bible_Deferred_Import',
            array(
                'bible_id' => $bibleId
            ), 'BibleImport_' . $bibleId, true);
    }

    /**
     * Gets the name of a Bible's title phrase.
     *
     * @param integer $bibleId
     *
     * @return string
     */
    public function getBibleTitlePhraseName($bibleId)
    {
        return 'th_bible_' . strtolower($bibleId) . '_bible';
    }

    /**
     * Gets a Bible's master title phrase text.
     *
     * @param integer $bibleId
     *
     * @return string
     */
    public function getBibleMasterTitlePhraseValue($bibleId)
    {
        $phraseName = $this->getBibleTitlePhraseName($bibleId);
        return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
    }

    /**
     * Constructs the title of the template that corresponds to a Bible.
     *
     * @param array $bible
     *
     * @return string
     */
    public function getTemplateTitle(array $bible)
    {
        if (!isset($bible['bible_id']) || $bible['bible_id'] === '') {
            throw new XenForo_Exception('Input bible array does not contain bible_id');
        }

        return self::BIBLE_TEMPLATE_PREFIX . $bible['bible_id'];
    }

    public function deleteTemporaryFiles($bibleId)
    {
        $internalDataPath = XenForo_Helper_File::getInternalDataPath();
        $path = $internalDataPath . DIRECTORY_SEPARATOR . 'bibles' . DIRECTORY_SEPARATOR . $bibleId;

        if (is_dir($path)) {
            $files = scandir($path);
            foreach ($files as $file) {
                if (!is_dir($file)) {
                    @unlink($path . "/" . $file);
                }
            }
            @rmdir($path);
        }
    }

    public function getDefaultUrlPortion(array $bible, $section = null)
    {
        $db = $this->_getDb();

        return $db->fetchOne(
            '
                SELECT url_portion
                FROM xf_bible_verse AS verse
                LEFT JOIN xf_bible_book AS book ON
                    (book.book_id = verse.book_id)
                WHERE bible_id = ?
                ' . ($section ? ' AND section = ' . $db->quote($section) : '') . '
                ORDER BY ' . ($section ? '' : 'FIELD(section, \'O\', \'N\', \'A\', \'\'), ') . 'book.priority ASC
            ', $bible['bible_id']);
    }

    /**
     * @return XenForo_Model_Phrase
     */
    protected function _getPhraseModel()
    {
        return $this->getModelFromCache('XenForo_Model_Phrase');
    }
}