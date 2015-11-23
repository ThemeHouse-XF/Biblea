<?php

class ThemeHouse_Bible_ControllerAdmin_Book extends XenForo_ControllerAdmin_Abstract
{

    /**
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionIndex()
    {
        $bookModel = $this->_getBookModel();
        
        $books = $bookModel->getBooks();
        $books = $bookModel->prepareBooks($books);
        
        $viewParams = array(
            'books' => $books,
            'sectionTitles' => $bookModel->getSectionTitles()
        );
        
        return $this->responseView('ThemeHouse_Bible_ViewAdmin_Book_List', 'th_book_list_bible', $viewParams);
    }

    /**
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    protected function _getBookAddEditResponse(array $book)
    {
        $bookModel = $this->_getBookModel();
        
        $sectionTitles = $bookModel->getSectionTitles();
        
        $bookNames = '';
        if (!empty($book['book_id'])) {
            $bookNames = implode(', ', 
                array_keys(
                    $bookModel->getBookNames(
                        array(
                            'book_id' => $book['book_id']
                        ))));
        }
        
        $viewParams = array(
            'book' => $book,
            'bookNames' => $bookNames,
            'sectionTitles' => $sectionTitles
        );
        
        return $this->responseView('ThemeHouse_Bible_ViewAdmin_Book_Edit', 'th_book_edit_bible', $viewParams);
    }

    /**
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionAdd()
    {
        $book = array();
        
        return $this->_getBookAddEditResponse($book);
    }

    /**
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionEdit()
    {
        $bookId = $this->_input->filterSingle('book_id', XenForo_Input::UINT);
        $book = $this->_getBookOrError($bookId);
        
        $book['title'] = $this->_getBookModel()->getBookMasterTitlePhraseValue($book['url_portion']);
        
        return $this->_getBookAddEditResponse($book);
    }

    /**
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionSave()
    {
        $this->_assertPostOnly();
        
        $bookId = $this->_input->filterSingle('book_id', XenForo_Input::UINT);
        $dwData = $this->_input->filter(
            array(
                'url_portion' => XenForo_Input::STRING,
                'priority' => XenForo_Input::UINT,
                'section' => XenForo_Input::STRING
            ));
        
        $dw = XenForo_DataWriter::create('ThemeHouse_Bible_DataWriter_Book');
        if ($bookId) {
            $dw->setExistingData($bookId);
        }
        $dw->setExtraData(ThemeHouse_Bible_DataWriter_Book::DATA_TITLE,
            $this->_input->filterSingle('title', XenForo_Input::STRING));
        $dw->setExtraData(ThemeHouse_Bible_DataWriter_Book::DATA_BOOK_NAMES,
            $this->_input->filterSingle('book_names', XenForo_Input::STRING));
        $dw->bulkSet($dwData);
        $dw->save();
        
        $bookId = $dw->get('book_id');
        
        return $this->responseRedirect(XenForo_ControllerResponse_Redirect::SUCCESS, 
            XenForo_Link::buildAdminLink('bible-books') . $this->getLastHash($bookId));
    }

    /**
     *
     * @return XenForo_ControllerResponse_Abstract
     */
    public function actionDelete()
    {
        if ($this->isConfirmedPost()) {
            return $this->_deleteData('ThemeHouse_Bible_DataWriter_Book', 'book_id',
                XenForo_Link::buildAdminLink('bible-books'));
        } else {
            $bookId = $this->_input->filterSingle('book_id', XenForo_Input::UINT);
            $book = $this->_getBookOrError($bookId);
            $book = $this->_getBookModel()->prepareBook($book);
            
            $viewParams = array(
                'book' => $book
            );
            
            return $this->responseView('ThemeHouse_Bible_ViewAdmin_Book_Delete', 'th_book_delete_bible',
                $viewParams);
        }
    }

    /**
     *
     * @return array
     */
    protected function _getBookOrError($bookId)
    {
        $bookModel = $this->_getBookModel();
        
        return $this->getRecordOrError($bookId, $bookModel, 'getBookById', 'th_book_not_found_bible');
    }

    /**
     *
     * @return ThemeHouse_Bible_Model_Book
     */
    protected function _getBookModel()
    {
        return $this->getModelFromCache('ThemeHouse_Bible_Model_Book');
    }
}