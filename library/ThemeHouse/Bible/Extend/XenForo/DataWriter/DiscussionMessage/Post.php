<?php
if (false) {

    class XFCP_ThemeHouse_Bible_Extend_XenForo_DataWriter_DiscussionMessage_Post extends XenForo_DataWriter_DiscussionMessage_Post
    {
    }
}

class ThemeHouse_Bible_Extend_XenForo_DataWriter_DiscussionMessage_Post extends XFCP_ThemeHouse_Bible_Extend_XenForo_DataWriter_DiscussionMessage_Post
{

    protected function _messagePostSave()
    {
        parent::_messagePostSave();
        
        if ($this->isChanged('message')) {
            $this->rebuildPostVerseCache($this->isUpdate());
        }
    }

    public function rebuildPostVerseCache($deleteFirst = true)
    {
        if ($deleteFirst) {
            $this->_db->query(
                '
                    DELETE FROM xf_post_bible_verse
                    WHERE post_id = ?
                ', $this->get('post_id'));
        }
        
        /* @var $bookModel ThemeHouse_Bible_Model_Book */
        $bookModel = $this->getModelFromCache('ThemeHouse_Bible_Model_Book');
        
        $bookCache = $bookModel->getBookCache();
        
        $bookNames = array();
        if ($bookCache) {
            foreach ($bookCache as $bookId => $book) {
                if (!empty($book['book_names'])) {
                    foreach ($book['book_names'] as $bookName) {
                        $bookNames[$bookName] = strlen($bookName);
                    }
                }
            }
        }
        
        if ($bookNames) {
            arsort($bookNames);
            
            $pattern = '#(?:' . implode('|', array_keys($bookNames)) . ') [0-9]+(?::[0-9]+(?:\-[0-9]+)?)?#i';
            
            if (preg_match_all($pattern, $this->get('message'), $matches)) {
                /* @var $verseModel ThemeHouse_Bible_Model_Verse */
                $verseModel = $this->getModelFromCache('ThemeHouse_Bible_Model_Verse');
                
                $values = array();
                
                foreach ($matches[0] as $text) {
                    $verse = $verseModel->parseVerse($text, true);
                    
                    if ($verse) {
                        $values[] = '(' . $this->get('post_id') . ',' . $verse['book_id'] . ',' . $verse['chapter'] . ',' .
                             $verse['verse'] . ',' . $verse['verse_to'] . ')';
                    }
                }
                
                if ($values) {
                    $values = array_unique($values);
                    $this->_db->query(
                        '
                            INSERT INTO xf_post_bible_verse
                            (post_id, book_id, chapter, verse, verse_to)
                            VALUES ' . implode(',', $values) . '
                        ');
                }
            }
        }
    }
}