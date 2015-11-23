<?php

class ThemeHouse_Bible_ViewPublic_Verse_Tooltip extends XenForo_ViewPublic_Base
{

    public function renderHtml()
    {
        if (!empty($this->_params['verse'])) {
            $formatter = XenForo_BbCode_Formatter_Base::create('XenForo_BbCode_Formatter_Base');
            $parser = XenForo_BbCode_Parser::create($formatter);
            
            $this->_params['verseParsed'] = $parser->render($this->_params['verse']);
        }
    }
}