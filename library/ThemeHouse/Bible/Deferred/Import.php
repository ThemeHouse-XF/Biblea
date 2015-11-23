<?php

class ThemeHouse_Bible_Deferred_Import extends XenForo_Deferred_Abstract
{

    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $data = array_merge(array(
            'position' => 0,
            'count' => 0,
            'imported' => XenForo_Application::$time
        ), $data);

        if (empty($data['bible_id'])) {
            return false;
        }

        $db = XenForo_Application::getDb();

        /* @var $bibleModel ThemeHouse_Bible_Model_Bible */
        $bibleModel = XenForo_Model::create('ThemeHouse_Bible_Model_Bible');

        /* @var $bookModel ThemeHouse_Bible_Model_Book */
        $bookModel = XenForo_Model::create('ThemeHouse_Bible_Model_Book');

        /* @var $verseModel ThemeHouse_Bible_Model_Verse */
        $verseModel = XenForo_Model::create('ThemeHouse_Bible_Model_Verse');

        $internalDataPath = XenForo_Helper_File::getInternalDataPath();
        $path = $internalDataPath . DIRECTORY_SEPARATOR . 'bibles' . DIRECTORY_SEPARATOR . $data['bible_id'];

        if (!file_exists($path) || !is_dir($path)) {
            return false;
        }

        $mappedFilename = $path . DIRECTORY_SEPARATOR . $data['bible_id'] . '_utf8_mapped_to_NRSVA.txt';
        $utf8Filename = $path . DIRECTORY_SEPARATOR . $data['bible_id'] . '_utf8.txt';
        $mappingFilename = $path . DIRECTORY_SEPARATOR . $data['bible_id'] . '_mapping_to_NRSVA.txt';

        if (file_exists($mappedFilename)) {
            $handle = fopen($mappedFilename, 'r');
        } elseif (file_exists($utf8Filename)) {
            $handle = fopen($utf8Filename, 'r');
        }

        $bibleInfo = array();
        $columns = array();
        $fields = array(
            'name',
            'copyright',
            'abbreviation',
            'language',
            'note'
        );
        if (isset($handle)) {
            $position = 0;
            while ($row = fgets($handle)) {
                $row = explode("\t", rtrim($row, "\r\n"));
                if (empty($row[0])) {
                    // do nothing
                } elseif (substr($row[0], 0, 1) != '#') {
                    fseek($handle, $position);
                    break;
                } elseif (in_array(substr($row[0], 1), $fields)) {
                    if (!empty($row[1])) {
                        $key = substr(array_shift($row), 1);
                        $bibleInfo[$key] = implode(',', $row);
                    } else {
                        $bibleInfo[substr($row[0], 1)] = '';
                    }
                } elseif (substr($row[0], 1) == 'columns') {
                    array_shift($row);
                    $columns = $row;
                }
                $position = ftell($handle);
            }
        }

        $bibleId = $data['bible_id'];
        $bible = $bibleModel->getBibleById($bibleId);

        $bookNamesFilename = $path . '/book_names.txt';

        if (!file_exists($bookNamesFilename)) {
            return false;
        }

        $bookIndexes = array();
        $bookNamesHandle = fopen($bookNamesFilename, 'r');
        while ($row = fgets($bookNamesHandle)) {
            $row = explode("\t", rtrim($row, "\r\n"));
            if (empty($row[0])) {
                continue;
            }
            $bookIndexes[$row[0]] = $row[1];
        }
        fclose($bookNamesHandle);

        $bookNameIds = array();

        if ($data['position'] == 0 || ($bibleInfo && !$bible)) {
            $bibleDw = XenForo_DataWriter::create('ThemeHouse_Bible_DataWriter_Bible');
            if ($bible) {
                $bibleDw->setExistingData($bible);
            } else {
                $bibleDw->set('bible_id', $bibleId);
            }
            if (strpos($bibleInfo['name'], ':') !== false) {
                $title = trim(substr($bibleInfo['name'], strpos($bibleInfo['name'], ':') + 2));
            } else {
                $title = $bibleInfo['name'];
            }
            $bibleDw->setExtraData(ThemeHouse_Bible_DataWriter_Bible::DATA_TITLE, $title);
            $bibleDw->bulkSet($bibleInfo);
            $bibleDw->save();

            $bible = $bibleDw->getMergedData();

            if ($bible) {
                $htmlFilename = $path . DIRECTORY_SEPARATOR . $bibleId . '.html';
                if (file_exists($htmlFilename)) {
                    $html = file_get_contents($htmlFilename);
                    preg_match('#<body>(.*)</body>#s', $html, $matches);
                    if (!empty($matches[1])) {
                        $html = $matches[1];
                    }
                } else {
                    $html = '';
                }

                $title = $bibleModel->getTemplateTitle(
                    array(
                        'bible_id' => $bibleId
                    ));

                /* @var $templateModel XenForo_Model_Template */
                $templateModel = XenForo_Model::create('XenForo_Model_Template');

                $template = $templateModel->getTemplateInStyleByTitle($title);

                if (!$template || $html) {
                    $templateWriter = XenForo_DataWriter::create('XenForo_DataWriter_Template');

                    if ($template) {
                        $templateWriter->setExistingData($template);
                    }

                    $templateWriter->set('title', $title);
                    $templateWriter->set('template', $html);
                    $templateWriter->set('style_id', 0);
                    $templateWriter->set('addon_id', '');

                    $templateWriter->save();
                }
            }

            $bookNamesFilename = $path . '/book_names.txt';

            if (file_exists($bookNamesFilename)) {
                $phraseModel = XenForo_Model::create('XenForo_Model_Phrase');

                $bibleBookNameValues = array();
                $priorities = array();
                foreach ($bookIndexes as $bookIndex => $bookTitle) {
                    $book = $bookModel->getBookByName(strtolower($bookTitle));
                    if (!$book) {
                        $urlPortion = strtolower(XenForo_Link::getTitleForUrl($bookTitle, true));
                        $book = $bookModel->getBookByUrlPortion($urlPortion);
                    } else {
                        $urlPortion = $book['url_portion'];
                    }

                    $title = $verseModel->getBookPhraseTitle($urlPortion);
                    $phraseModel->insertOrUpdateMasterPhrase($title, $bookTitle, '', array(),
                        array(
                            XenForo_DataWriter_Phrase::OPTION_REBUILD_LANGUAGE_CACHE => false,
                            XenForo_DataWriter_Phrase::OPTION_RECOMPILE_TEMPLATE => false
                        ));

                    if ($book) {
                        $bookId = $book['book_id'];
                        $priorities[$book['section']] = $book['priority'];
                    } else {
                        $section = substr($bookIndex, -1, 1);
                        if (isset($priorities[$section])) {
                            $priorities[$section] = $priorities[$section] + 10;
                        } else {
                            $priorities[$section] = 10;
                        }
                        $db->query(
                            '
                                UPDATE xf_bible_book
                                SET priority = priority + 10
                                WHERE priority >= ? AND section = ?
                            ',
                            array(
                                $priorities[$section],
                                $section
                            ));
                        $db->query(
                            '
                                INSERT INTO xf_bible_book
                                (url_portion, priority, section)
                                VALUES (?, ?, ?)
                            ',
                            array(
                                $urlPortion,
                                $priorities[$section],
                                $section
                            ));
                        $bookId = $db->lastInsertId();
                        $bibleBookNameValues[] = '(' . $db->quote(strtolower($bookTitle)) . ',' . $db->quote($bookId) .
                             ')';
                    }
                    $bookNameIds[strtolower($bookTitle)] = $bookId;
                }

                if ($bibleBookNameValues) {
                    $db->query(
                        '
                            INSERT INTO xf_bible_book_name
                            (book_name, book_id)
                            VALUES ' . implode(',', $bibleBookNameValues) . '
                            ON DUPLICATE KEY UPDATE book_name = book_name
                        ');
                }
            }
        }

        if (!isset($handle) || empty($columns)) {
            @fclose($handle);
            $bibleModel->deleteTemporaryFiles($bibleId);
            return false;
        }

        $requiredColumns = array(
            'orig_book_index',
            'orig_chapter',
            'orig_verse',
            'text'
        );

        foreach ($requiredColumns as $requiredColumn) {
            if (!in_array($requiredColumn, $columns)) {
                fclose($handle);
                $bibleModel->deleteTemporaryFiles($bibleId);
                return false;
            }
        }

        $startTime = microtime(true);

        if (ftell($handle) < $data['position']) {
            $seek = fseek($handle, $data['position']);
            $row = explode("\t", rtrim(fgets($handle), "\r\n"));
            if (feof($handle)) {
                fclose($handle);
                $bibleModel->deleteTemporaryFiles($bibleId);
                return false;
            }
        }

        while ($row = fgets($handle)) {
            $row = explode("\t", rtrim($row, "\r\n"));
            if (empty($row[0])) {
                continue;
            }
            $data['position'] = ftell($handle);
            $verseData = array_combine($columns, $row);

            $bookName = strtolower($bookIndexes[$verseData['orig_book_index']]);
            if (!isset($bookNameIds[$bookName])) {
                $book = $bookModel->getBookByName($bookName);
                $bookNameIds[$bookName] = $book['book_id'];
            }

            $bookId = $bookNameIds[$bookName];
            $origChapter = $verseData['orig_chapter'];
            $origVerse = $verseData['orig_verse'];
            $text = $verseData['text'];

            $verse = $verseModel->getSpecificVerse($bibleId, $bookId, $origChapter, $origVerse);

            // TODO manage subverses here?

            $verseDw = XenForo_DataWriter::create('ThemeHouse_Bible_DataWriter_Verse');
            if ($verse) {
                $verseDw->setExistingData($verse);
            }
            $verseDw->bulkSet(
                array(
                    'bible_id' => $bibleId,
                    'book_id' => $bookId,
                    'chapter' => $origChapter,
                    'verse' => $origVerse,
                    'text' => $text
                ));
            $verseDw->save();
            $data['count']++;

            if ($targetRunTime && (microtime(true) - $startTime) > $targetRunTime) {
                break;
            }
        }

        if (feof($handle)) {
            fclose($handle);
            $bibleModel->deleteTemporaryFiles($bibleId);

            $bookModel->rebuildBookCache();

            return false;
        }

        $actionPhrase = new XenForo_Phrase('importing');
        $typePhrase = new XenForo_Phrase('th_bible_verses_bible');
        $status = sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($data['count']));

        return $data;
    }
}