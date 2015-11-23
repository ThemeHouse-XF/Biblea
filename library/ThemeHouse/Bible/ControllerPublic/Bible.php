<?php

class ThemeHouse_Bible_ControllerPublic_Bible extends XenForo_ControllerPublic_Abstract
{

    public function actionIndex()
    {
        $urlPortion = $this->_input->filterSingle('url_portion', XenForo_Input::STRING);
        $chapter = $this->_input->filterSingle('chapter', XenForo_Input::UINT);
        $verse = $this->_input->filterSingle('verse', XenForo_Input::UINT);
        $verseTo = $this->_input->filterSingle('verse_to', XenForo_Input::UINT);
        $bibleId = $this->_input->filterSingle('bible_id', XenForo_Input::STRING);

        $xenOptions = XenForo_Application::get('options');

        if (!$bibleId) {
            $bibleId = $xenOptions->th_bible_defaultBible;
        }

        $bibleModel = $this->_getBibleModel();

        if ($bibleId) {
            $bible = $bibleModel->getBibleById($bibleId);
        }

        if (empty($bible)) {
            return $this->responseError(new XenForo_Phrase('th_requested_bible_translation_not_found_bible'), 404);
        }

        $bible = $bibleModel->prepareBible($bible);

        if (!$chapter) {
            $chapter = 1;
        }

        $section = null;
        if ($urlPortion == 'new-testament') {
            $section = 'N';
        } elseif ($urlPortion == 'old-testament') {
            $section = 'O';
        } elseif ($urlPortion == 'apocrypha') {
            $section = 'A';
        }

        if (!$urlPortion || $section) {
            $urlPortion = $bibleModel->getDefaultUrlPortion($bible, $section);
        }

        $navParams = array(
            'url_portion' => $urlPortion,
            'chapter' => $chapter,
            'verse' => $verse,
            'verse_to' => $verseTo
        );

        $this->canonicalizeRequestUrl(
            XenForo_Link::buildPublicLink('bible', $navParams,
                array(
                    'bible_id' => $bibleId
                )));

        $conditions = array(
            'bible_id' => $bibleId,
            'chapter' => $chapter,
            'url_portion' => $urlPortion
        );

        /* @var $verseModel ThemeHouse_Bible_Model_Verse */
        $verseModel = $this->getModelFromCache('ThemeHouse_Bible_Model_Verse');

        $verseCount = 0;
        if ($verse) {
            $verseCount = $verseModel->countVerses($conditions);

            if ($verseTo) {
                $conditions['verses'] = array(
                    $verse,
                    $verseTo
                );
            } else {
                $conditions['verse'] = $verse;
            }
        }

        $fetchOptions = array(
            'join' => ThemeHouse_Bible_Model_Verse::FETCH_BIBLE_BOOK
        );

        $verses = $verseModel->getVerses($conditions, $fetchOptions);

        $verses = $verseModel->prepareVerses($verses);

        if (!$verses) {
            return $this->responseNoPermission();
        }

        $lastVerse = end($verses);
        $firstVerse = reset($verses);

        $chapterCount = $this->_getBookModel()->countChaptersInBook($firstVerse['book_id'], $bibleId);

        $chapters = array();
        for ($i = 2; $i < $chapterCount; $i++) {
            $chapters[] = $i;
        }

        if ($firstVerse == $lastVerse) {
            $title = $firstVerse['book_title'] . ' ' . $firstVerse['chapter'] . ':' . $firstVerse['verse'];
        } elseif ($verseCount && $firstVerse['chapter'] == $lastVerse['chapter']) {
            $title = $firstVerse['book_title'] . ' ' . $firstVerse['chapter'] . ':' . $firstVerse['verse'] . '-' .
                 $lastVerse['verse'];
        } elseif ($verseCount) {
            $title = $firstVerse['book_title'] . ' ' . $firstVerse['chapter'] . ':' . $firstVerse['verse'] . '-' .
                 $lastVerse['chapter'] . ':' . $lastVerse['verse'];
        } else {
            $title = $firstVerse['book_title'] . ' ' . $firstVerse['chapter'];
        }

        $postIds = $verseModel->getPostIdsForVerse($firstVerse['book_id'], $chapter, $verse, $verseTo);

        $posts = array();
        if ($postIds) {
            $postIds = array_slice($postIds, -5, 5, true);

            $viewingUser = XenForo_Visitor::getInstance()->toArray();
            $postModel = $this->_getPostModel();

            $fetchOptions = array(
                'limit' => 5,
                'join' => XenForo_Model_Post::FETCH_THREAD | XenForo_Model_Post::FETCH_FORUM | XenForo_Model_Post::FETCH_USER,
                'permissionCombinationId' => $viewingUser['permission_combination_id'],
            );

            $posts = $postModel->getPostsByIds($postIds, $fetchOptions);

            krsort($posts);

            $posts = $postModel->unserializePermissionsInList($posts, 'node_permission_cache');

            foreach ($posts as $postId => $post) {
                if (!$postModel->canViewPostAndContainer($post, $post, $post, $errorPhraseKey, $post['permissions'], $viewingUser)) {
                    unset($posts[$postId]);
                    continue;
                }
                $posts[$postId] = $this->_getPostModel()->preparePost($post, $post, $post, $post['permissions'], $viewingUser);
                $posts[$postId]['title'] = XenForo_Helper_String::censorString($post['title']);
            }
        }

        $viewParams = array(
            'bible' => $bible,

            'verses' => $verses,

            'firstVerse' => $firstVerse,
            'lastVerse' => $lastVerse,

            'chapterCount' => $chapterCount,
            'chapters' => $chapters,

            'verseCount' => $verseCount,

            'title' => $title,
            'navParams' => $navParams,

            'posts' => $posts
        );

        return $this->responseView('ThemeHouse_Bible_ViewPublic_Bible_View', 'th_bible_view_bible', $viewParams);
    }

    public function actionBooks()
    {
        $bibleId = $this->_input->filterSingle('bible_id', XenForo_Input::STRING);
        $bookId = $this->_input->filterSingle('book_id', XenForo_Input::UINT);

        $xenOptions = XenForo_Application::get('options');

        if (!$bibleId) {
            $bibleId = $xenOptions->th_bible_defaultBible;
        }

        $bookModel = $this->_getBookModel();

        $books = $bookModel->getBooksForBible($bibleId);
        $books = $bookModel->prepareBooks($books);

        $viewParams = array(
            'books' => $books,
            'selectedBookId' => $bookId,
            'bibleId' => $bibleId
        );

        return $this->responseView('ThemeHouse_Bible_ViewPublic_Bible_Books', 'th_bible_book_menu_bible',
            $viewParams);
    }

    public function actionTranslations()
    {
        $bibleId = $this->_input->filterSingle('bible_id', XenForo_Input::STRING);
        $bookId = $this->_input->filterSingle('book_id', XenForo_Input::UINT);
        $chapter = $this->_input->filterSingle('chapter', XenForo_Input::UINT);

        $xenOptions = XenForo_Application::get('options');

        if (!$bibleId) {
            $bibleId = $xenOptions->th_bible_defaultBible;
        }

        $bibleModel = $this->_getBibleModel();

        $viewParams = array(
            'book_id' => $bookId,
            'chapter' => $chapter
        );

        $bibles = $bibleModel->getBibles($viewParams);
        $bibles = $bibleModel->prepareBibles($bibles);

        $bookModel = $this->_getBookModel();

        $book = $bookModel->getBookbyId($bookId);

        $viewParams = array(
            'bibles' => $bibles,
            'selectedBibleId' => $bibleId,
            'book' => $book,
            'chapter' => $chapter
        );

        return $this->responseView('ThemeHouse_Bible_ViewPublic_Bible_Translations', 'th_bible_menu_bible',
            $viewParams);
    }

    public function actionPreview()
    {
        $urlPortion = $this->_input->filterSingle('url_portion', XenForo_Input::STRING);
        $chapter = $this->_input->filterSingle('chapter', XenForo_Input::UINT);
        $verse = $this->_input->filterSingle('verse', XenForo_Input::UINT);
        $verseTo = $this->_input->filterSingle('verse_to', XenForo_Input::UINT);
        $bibleId = $this->_input->filterSingle('bible_id', XenForo_Input::STRING);

        $xenOptions = XenForo_Application::get('options');

        if (!$bibleId) {
            $bibleId = $xenOptions->th_bible_defaultBible;
        }

        $bibleModel = $this->_getBibleModel();

        if ($bibleId) {
            $bible = $bibleModel->getBibleById($bibleId);
        }

        if (!$bible) {
            return $this->responseError(new XenForo_Phrase('th_requested_bible_translation_not_found_bible'), 404);
        }

        $bible = $bibleModel->prepareBible($bible);

        if (!$chapter) {
            $chapter = 1;
        }

        if (!$verse) {
            $verse = 1;
            $verseTo = $xenOptions->th_bible_tooltipVerseLimit + 1;
        }

        /* @var $bookModel ThemeHouse_Bible_Model_Book */
        $bookModel = XenForo_Model::create('ThemeHouse_Bible_Model_Book');

        $book = $bookModel->getBookByUrlPortion($urlPortion);

        /* @var $verseModel ThemeHouse_Bible_Model_Verse */
        $verseModel = $this->getModelFromCache('ThemeHouse_Bible_Model_Verse');

        $truncated = false;
        if ($verseTo - $verse >= $xenOptions->th_bible_tooltipVerseLimit) {
            $verseTo = $verse + $xenOptions->th_bible_tooltipVerseLimit - 1;
            $truncated = true;
        }

        $verse = $verseModel->getFormattedVerse($bibleId, $book['book_id'], $chapter, $verse, $verseTo, false, "\n");

        if (!$verse) {
            return $this->responseNoPermission();
        }

        $viewParams = array(
            'bible' => $bible,
            'verse' => $verse,
            'truncated' => $truncated
        );

        return $this->responseView('ThemeHouse_Bible_ViewPublic_Verse_Tooltip', 'th_bible_verse_tooltip_bible', $viewParams);
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
    /**
     *
     * @return XenForo_Model_Post
     */
    protected function _getPostModel()
    {
        return $this->getModelFromCache('XenForo_Model_Post');
    }
}