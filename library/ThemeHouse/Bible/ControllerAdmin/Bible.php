<?php

class ThemeHouse_Bible_ControllerAdmin_Bible extends XenForo_ControllerAdmin_Abstract
{

    /**
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionIndex()
    {
        $bibleModel = $this->_getBibleModel();
        
        $viewParams = array(
            'bibles' => $this->_getBibleModel()->getBibles()
        );
        
        return $this->responseView('ThemeHouse_Bible_ViewAdmin_Bible_List', 'th_bible_list_bible', $viewParams);
    }

    /**
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    protected function _getBibleAddEditResponse(array $bible)
    {
        $bibleModel = $this->_getBibleModel();
        
        if (!empty($bible['bible_id'])) {
            $template = $this->_getTemplateModel()->getTemplateInStyleByTitle($bibleModel->getTemplateTitle($bible));
            
            $bookModel = $this->_getBookModel();
            $sections = $bookModel->getSectionsForBible($bible['bible_id']);
            $sectionTitles = $bookModel->getSectionTitles();
            
            $bible['title'] = $bibleModel->getBibleMasterTitlePhraseValue($bible['bible_id']);
        } else {
            $sections = array();
            $sectionTitles = array();
        }
        if (empty($template)) {
            $template = array(
                'template' => ''
            );
        }
        
        $viewParams = array(
            'bible' => $bible,
            'template' => $template,
            
            'sections' => $sections,
            'sectionTitles' => $sectionTitles
        );
        
        return $this->responseView('ThemeHouse_Bible_ViewAdmin_Bible_Edit', 'th_bible_edit_bible', $viewParams);
    }

    /**
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionAdd()
    {
        $bible = array();
        
        return $this->_getBibleAddEditResponse($bible);
    }

    /**
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionEdit()
    {
        $bibleId = $this->_input->filterSingle('bible_id', XenForo_Input::STRING);
        $bible = $this->_getBibleOrError($bibleId);
        
        return $this->_getBibleAddEditResponse($bible);
    }

    /**
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionSave()
    {
        $this->_assertPostOnly();
        
        $bibleId = $this->_input->filterSingle('bible_id', XenForo_Input::STRING);
        $dwData = $this->_input->filter(
            array(
                'name' => XenForo_Input::STRING,
                'copyright' => XenForo_Input::STRING,
                'abbreviation' => XenForo_Input::STRING,
                'language' => XenForo_Input::STRING,
                'note' => XenForo_Input::STRING
            ));
        
        $dw = XenForo_DataWriter::create('ThemeHouse_Bible_DataWriter_Bible');
        if ($bibleId) {
            $dw->setExistingData($bibleId);
        } else {
            $dw->set('bible_id', $this->_input->filterSingle('bible_id', XenForo_Input::STRING));
        }
        $dw->bulkSet($dwData);
        $dw->setExtraData(ThemeHouse_Bible_DataWriter_Bible::DATA_TITLE,
            $this->_input->filterSingle('title', XenForo_Input::STRING));
        $dw->save();
        
        // TODO save template
        
        $bibleId = $dw->get('bible_id');
        
        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, 
            XenForo_Link::buildAdminLink('bibles') . $this->getLastHash($bibleId));
    }

    /**
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionDelete()
    {
        if ($this->isConfirmedPost()) {
            return $this->_deleteData('ThemeHouse_Bible_DataWriter_Bible', 'bible_id',
                XenForo_Link::buildAdminLink('bibles'));
        } else {
            $bibleId = $this->_input->filterSingle('bible_id', XenForo_Input::STRING);
            $bible = $this->_getBibleOrError($bibleId);
            
            $viewParams = array(
                'bible' => $bible
            );
            
            return $this->responseView('ThemeHouse_Bible_ViewAdmin_Bible_Delete', 'th_bible_delete_bible',
                $viewParams);
        }
    }

    public function actionImport()
    {
        $bibleModel = $this->_getBibleModel();
        
        if ($this->isConfirmedPost()) {
            $upload = XenForo_Upload::getUploadedFile('upload');
            if (!$upload) {
                return $this->responseError(new XenForo_Phrase('th_please_upload_valid_bible_zip_file_bible'));
            }
            
            $bibleModel->importBibleZip($upload);
            
            return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, 
                XenForo_Link::buildAdminLink('bibles'));
        } else {
            return $this->responseView('ThemeHouse_Bible_ViewAdmin_Bible_Import', 'th_bible_import_bible');
        }
    }

    /**
     *
     * @return array
     */
    protected function _getBibleOrError($bibleId)
    {
        return $this->getRecordOrError($bibleId, $this->_getBibleModel(), 'getBibleById', 
            'th_bible_not_found_bible');
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
     * @return XenForo_Model_Template
     */
    protected function _getTemplateModel()
    {
        return $this->getModelFromCache('XenForo_Model_Template');
    }
}