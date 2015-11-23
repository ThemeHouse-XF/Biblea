<?php

class ThemeHouse_Bible_ViewPublic_Bible_View extends XenForo_ViewPublic_Base
{

    public function renderHtml()
    {
        if (!empty($this->_params['posts'])) {
            foreach ($this->_params['posts'] as &$post) {
                $formatter = XenForo_BbCode_Formatter_Base::create('XenForo_BbCode_Formatter_Text');
                $parser = XenForo_BbCode_Parser::create($formatter);
                
                $post['messageParsed'] = $parser->render($post['message']);
            }
        }
    }
}