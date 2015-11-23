<?php

class ThemeHouse_Bible_BbCode_Verse
{

    public static function quoteVerse(array $tag, array $rendererStates, XenForo_BbCode_Formatter_Base $formatter)
    {
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
        
        if (!$verse && count($tag['children']) == 1 && is_string($tag['children'][0])) {
            $bibleId = $version ? strtolower($version) : null;
            
            /* @var $verseModel ThemeHouse_Bible_Model_Verse */
            $verseModel = XenForo_Model::create('ThemeHouse_Bible_Model_Verse');
            
            $verseText = $verseModel->getVerseFromText($tag['children'][0], $bibleId, true);
            
            if ($verseText) {
                $verse = $tag['children'][0];
                $version = strtoupper($bibleId);
                
                $tag['children'] = $verseText;
            }
        }
        
        $link = '';
        $params = array(
            'bible_id' => strtolower($version)
        );
        
        if (in_array(strtolower($version), $xenOptions->th_bible_bbCodeBibles)) {
            if (preg_match('#^(.*)\s+([0-9\-:]+)$#', $verse, $matches)) {
                $urlPortion = strtolower(XenForo_Link::getTitleForUrl($matches[1], true));
                $link = XenForo_Link::buildPublicLink('bible/' . $urlPortion . '/' . $matches[2] . '/', null, $params);
                
                /* @var $bibleModel ThemeHouse_Bible_Model_Bible */
                $bibleModel = XenForo_Model::create('ThemeHouse_Bible_Model_Bible');
                
                $titlePhraseName = $bibleModel->getBibleTitlePhraseName(strtolower($version));
                
                $bibleTitle = new XenForo_Phrase($titlePhraseName);
                $bibleTitle->setPhraseNameOnInvalid(false);
                
                if ((string) $bibleTitle) {
                    $version = $bibleTitle;
                }
            } else {
                $urlPortion = strtolower(XenForo_Link::getTitleForUrl($verse, true));
                $link = XenForo_Link::buildPublicLink('bible/' . $urlPortion . '/', null, $params);
            }
        } else {
            $link = '';
        }
        
        $content = $formatter->renderSubTree($tag['children'], $rendererStates);
        
        $view = $formatter->getView();
        if ($view) {
            $template = $view->createTemplateObject('th_bb_code_verse_bible',
                array(
                    'content' => $content,
                    'verse' => $verse,
                    'version' => $version,
                    'link' => $link
                ));
            $content = $template->render();
            return trim($content);
        }
        
        return $content;
    }
}