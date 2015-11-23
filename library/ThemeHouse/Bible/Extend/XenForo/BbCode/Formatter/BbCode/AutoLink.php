<?php
if (false) {

    class XFCP_ThemeHouse_Bible_Extend_XenForo_BbCode_Formatter_BbCode_AutoLink extends XenForo_BbCode_Formatter_BbCode_AutoLink
    {
    }
}

class ThemeHouse_Bible_Extend_XenForo_BbCode_Formatter_BbCode_AutoLink extends XFCP_ThemeHouse_Bible_Extend_XenForo_BbCode_Formatter_BbCode_AutoLink
{

    public function autoLinkTag(array $tag, array $rendererStates)
    {
        $text = parent::autoLinkTag($tag, $rendererStates);
        
        if ($tag['tag'] == 'verse') {
            $bibleId = null;
            
            $option = explode(',', $tag['option']);
            
            $xenOptions = XenForo_Application::get('options');
            
            $verse = '';
            $version = null;
            if (count($option) > 1) {
                $verse = $option[0];
                $version = strtoupper($option[1]);
            } elseif ($option[0]) {
                if (preg_match('#^[A-z]+$#', $tag['option'])) {
                    $verse = '';
                    $version = strtoupper($tag['option']);
                } else {
                    $verse = $tag['option'];
                    $version = strtoupper($xenOptions->th_bible_defaultBible);
                }
            }
            
            if (in_array(strtolower($version), $xenOptions->th_bible_bbCodeBibles)) {
                $bibleId = strtolower($version);
            } elseif ($verse) {
                return $text;
            } else {
                $bibleId = null;
            }
            
            /* @var $verseModel ThemeHouse_Bible_Model_Verse */
            $verseModel = XenForo_Model::create('ThemeHouse_Bible_Model_Verse');
            
            if (!$verse) {
                $verse = XenForo_Helper_String::bbCodeStrip($text);
            }
            
            $verseText = $verseModel->getVerseFromText($verse, $bibleId);
            
            if ($verseText) {
                $verseTextStripped = XenForo_Helper_String::bbCodeStrip(str_ireplace('versenum', 'quote', $verseText), true);
                $textStripped = XenForo_Helper_String::bbCodeStrip(str_ireplace('versenum', 'quote', $text), true);
                $verseTextStripped = strtolower(preg_replace('!\s+!', ' ', $verseTextStripped));
                
                // remove square brackets (i.e., extra comments)
                $textStripped = preg_replace('/\[[^\]]*\]/', '', $textStripped);
                // remove double spaces, line breaks, etc.
                $textStripped = strtolower(preg_replace('!\s+!', ' ', $textStripped));
                // split at elipsis
                $textStrippedSplit = preg_split("#(?:\xE2\x80\xA6)|(?:\.\.\.)#", $textStripped);
                
                foreach ($textStrippedSplit as $_textStripped) {
                    if (!trim($_textStripped)) {
                        continue;
                    }
                    $strPos = strpos($verseTextStripped, $_textStripped);
                    if ($strPos === false) {
                        return '[VERSE=' . $verse . ',' . strtoupper($bibleId) . ']' . $verseText . '[/VERSE]';
                    } else {
                        $verseTextStripped = substr($verseTextStripped, $strPos + strlen($_textStripped));
                    }
                }
                
            }
        }
        
        return $text;
    }

    public function filterString($string, array $rendererStates)
    {
        $string = parent::filterString($string, $rendererStates);
        
        if (empty($rendererStates['stopAutoLink'])) {
            // do nothing
        }
        
        return $string;
    }
}