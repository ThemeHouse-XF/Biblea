<?php

class ThemeHouse_Bible_DataWriter_Bible extends XenForo_DataWriter
{

    /**
     * Constant for extra data that holds the value for the phrase
     * that is the title of this section.
     *
     * This value is required on inserts.
     *
     * @var string
     */
    const DATA_TITLE = 'phraseTitle';

    /**
     * Gets the fields that are defined for the table.
     * See parent for explanation.
     *
     * @return array
     */
    protected function _getFields()
    {
        return array(
            'xf_bible' => array(
                'bible_id' => array(
                    'type' => self::TYPE_STRING,
                    'required' => true
                ),
                'name' => array(
                    'type' => self::TYPE_STRING,
                    'required' => true
                ),
                'copyright' => array(
                    'type' => self::TYPE_STRING,
                    'default' => ''
                ),
                'abbreviation' => array(
                    'type' => self::TYPE_STRING,
                    'default' => ''
                ),
                'language' => array(
                    'type' => self::TYPE_STRING,
                    'default' => 'eng'
                ),
                'note' => array(
                    'type' => self::TYPE_STRING,
                    'default' => ''
                ),
                'last_modified' => array(
                    'type' => self::TYPE_UINT,
                    'default' => XenForo_Application::$time
                )
            )
        );
    }

    /**
     * Gets the actual existing data out of data that was passed in.
     * See parent for explanation.
     *
     * @param mixed
     *
     * @return array|false
     */
    protected function _getExistingData($data)
    {
        if (!$bibleId = $this->_getExistingPrimaryKey($data, 'bible_id')) {
            return false;
        }
        
        $bible = $this->_getBibleModel()->getBibleById($bibleId);
        if (!$bible) {
            return false;
        }
        
        return $this->getTablesDataFromArray($bible);
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return 'bible_id = ' . $this->_db->quote($this->getExisting('bible_id'));
    }

    /**
     * Pre-save handling.
     */
    protected function _preSave()
    {
        $titlePhrase = $this->getExtraData(self::DATA_TITLE);
        if ($titlePhrase !== null && strlen($titlePhrase) == 0) {
            $this->error(new XenForo_Phrase('please_enter_valid_title'), 'title');
        }
    }

    /**
     * Post-save handling.
     */
    protected function _postSave()
    {
        $titlePhrase = $this->getExtraData(self::DATA_TITLE);
        if ($titlePhrase !== null) {
            $this->_insertOrUpdateMasterPhrase($this->_getTitlePhraseName($this->get('bible_id')), $titlePhrase, '', 
                array(
                    'global_cache' => true
                ));
        }
        
        $xenOptions = XenForo_Application::get('options');
        
        if (!$xenOptions->th_bible_defaultBible) {
            /* @var $dw XenForo_DataWriter_Option */
            $dw = XenForo_DataWriter::create('XenForo_DataWriter_Option');
            $dw->setExistingData('th_bible_defaultBible');
            $dw->set('option_value', $this->get('bible_id'));
            $dw->save();
        }
        
        if ($this->isInsert()) {
            $bbCodeBibles = $xenOptions->th_bible_bbCodeBibles;
            $bbCodeBibles[] = $this->get('bible_id');
            /* @var $dw XenForo_DataWriter_Option */
            $dw = XenForo_DataWriter::create('XenForo_DataWriter_Option');
            $dw->setExistingData('th_bible_bbCodeBibles');
            $dw->set('option_value', $bbCodeBibles);
            $dw->save();
        }
    }

    protected function _postDelete()
    {
        $bibleId = $this->get('bible_id');
        
        $this->_deleteMasterPhrase($this->_getTitlePhraseName($bibleId));
        
        $template = $this->getModelFromCache('XenForo_Model_Template')->getTemplateInStyleByTitle(
            $this->_getBibleModel()
                ->getTemplateTitle($this->getMergedData()));
        if ($template) {
            $dw = XenForo_DataWriter::create('XenForo_DataWriter_Template', XenForo_DataWriter::ERROR_SILENT);
            $dw->setExistingData($template, true);
            $dw->delete();
        }

        XenForo_Application::defer('ThemeHouse_Bible_Deferred_VerseDelete', array('bible_id' => $bibleId), "verseDelete_$bibleId", true);
    }

    /**
     * Gets the name of the Bible's title phrase.
     *
     * @param string $id
     *
     * @return string
     */
    protected function _getTitlePhraseName($id)
    {
        return $this->_getBibleModel()->getBibleTitlePhraseName($id);
    }

    /**
     *
     * @return ThemeHouse_Bible_Model_Bible
     */
    protected function _getBibleModel()
    {
        return $this->getModelFromCache('ThemeHouse_Bible_Model_Bible');
    }
}