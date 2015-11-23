<?php
if (false) {

    class XFCP_ThemeHouse_Bible_Extend_XenForo_BbCode_Formatter_Base extends XenForo_BbCode_Formatter_Base
    {
    }
}

class ThemeHouse_Bible_Extend_XenForo_BbCode_Formatter_Base extends XFCP_ThemeHouse_Bible_Extend_XenForo_BbCode_Formatter_Base
{

    /**
     *
     * @var ThemeHouse_Bible_Model_Verse
     */
    protected $_verseModel = null;

    /**
     *
     * @var ThemeHouse_Bible_Model_Book
     */
    protected $_bookModel = null;

    public function renderTag(array $element, array $rendererStates, &$trimLeadingLines)
    {
        if ($element['tag'] == 'url') {
            $rendererStates['noVerseLinking'] = true;
        }
        
        return parent::renderTag($element, $rendererStates, $trimLeadingLines);
    }

    public function filterString($string, array $rendererStates)
    {
        $string = parent::filterString($string, $rendererStates);
       
        $bookCache = $this->_getBookModel()->getBookCache();
        
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
            
            $pattern = '#(?:' . implode('|', array_keys($bookNames)) . ') [0-9]+(?::[0-9]+(?:\-[0-9]+)?)?(?: \([A-Z_]+\))?#i';
            
            if (empty($rendererStates['plainChildren']) && empty($rendererStates['noVerseLinking'])) {
                $string = preg_replace_callback($pattern, 
                    array(
                        $this,
                        'renderVerse'
                    ), $string);
            }
        }
        
        return $string;
    }

    public function renderVerse(array $matches)
    {
        $text = $matches[0];
        
        $pattern = '#^(.*) \(([A-Z_]+)\)$#';
        
        if (preg_match($pattern, $text, $matches)) {
            $verse = $matches[1];
            $version = $matches[2];
        } else {
            $verse = $text;
            $version = null;
        }
        
        $xenOptions = XenForo_Application::get('options');
        
        if (!$version || !in_array(strtolower($version), $xenOptions->th_bible_bbCodeBibles)) {
            $bibleId = null;
        } else {
            $bibleId = strtolower($version);
        }
        
        $parsedVerse = $this->_getVerseModel()->parseVerse($verse, true);
        $verse = $parsedVerse['text'];
        
        if ($parsedVerse) {
            $link = XenForo_Link::buildPublicLink('bible', $parsedVerse, array(
                'bible_id' => $bibleId
            ));
            $previewLink = XenForo_Link::buildPublicLink('bible/preview', $parsedVerse, array(
                'bible_id' => $bibleId
            ));
            $showTooltip = $xenOptions->th_bible_tooltipVerseLimit;
            return '<a href="' . $link . '" title="" ' .
                 ($showTooltip ? 'class="VerseTooltip" data-verseUrl="' . $previewLink . '"' : '') . '>' . $verse .
                 '</a>' . ($bibleId || !$version ? '' : ' (' . $version . ')');
        } else {
            return $text;
        }
    }

    /**
     *
     * @return ThemeHouse_Bible_Model_Book
     */
    protected function _getBookModel()
    {
        if (!$this->_bookModel) {
            $this->_bookModel = XenForo_Model::create('ThemeHouse_Bible_Model_Book');
        }
        
        return $this->_bookModel;
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