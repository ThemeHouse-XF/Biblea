<?php

class ThemeHouse_Bible_Deferred_PostVerse extends XenForo_Deferred_Abstract
{

    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $data = array_merge(array(
            'position' => 0,
            'batch' => 70
        ), $data);
        $data['batch'] = max(1, $data['batch']);
        
        /* @var $postModel XenForo_Model_Post */
        $postModel = XenForo_Model::create('XenForo_Model_Post');
        
        $postIds = $postModel->getPostIdsInRange($data['position'], $data['batch']);
        if (sizeof($postIds) == 0) {
            return true;
        }
        
        foreach ($postIds as $postId) {
            $data['position'] = $postId;
            
            /* @var $postDw XenForo_DataWriter_DiscussionMessage_Post */
            $postDw = XenForo_DataWriter::create('XenForo_DataWriter_DiscussionMessage_Post', 
                XenForo_DataWriter::ERROR_SILENT);
            if ($postDw->setExistingData($postId)) {
                XenForo_Db::beginTransaction();
                
                $postDw->rebuildPostVerseCache();
                
                XenForo_Db::commit();
            }
        }
        
        $actionPhrase = new XenForo_Phrase('rebuilding');
        $typePhrase = new XenForo_Phrase('posts');
        $status = sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($data['position']));
        
        return $data;
    }

    public function canCancel()
    {
        return true;
    }
}