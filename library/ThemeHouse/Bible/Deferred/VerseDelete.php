<?php

class ThemeHouse_Bible_Deferred_VerseDelete extends XenForo_Deferred_Abstract
{

    public function canTriggerManually()
    {
        return false;
    }

    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        if (!isset($data['conditions'])) {
            $data = array(
                'conditions' => $data
            );
        }
        
        $data = array_merge(
            array(
                'conditions' => false,
                'count' => 0,
                'total' => null
            ), $data);
        
        if (!$data['conditions']) {
            return false;
        }
        
        $s = microtime(true);
        
        /* @var $verseModel ThemeHouse_Bible_Model_Verse */
        $verseModel = XenForo_Model::create('ThemeHouse_Bible_Model_Verse');
        
        if ($data['total'] === null) {
            $data['total'] = $verseModel->countVerses($data['conditions']);
            if (!$data['total']) {
                return false;
            }
        }
        
        $verseIds = $verseModel->getVerseIds($data['conditions'], array(
            'limit' => 1000
        ));
        if (!$verseIds) {
            return false;
        }
        
        $continue = count($verseIds) < 1000 ? false : true;
        
        foreach ($verseIds as $verseId) {
            $dw = XenForo_DataWriter::create('ThemeHouse_Bible_DataWriter_Verse', XenForo_DataWriter::ERROR_SILENT);
            if ($dw->setExistingData($verseId)) {
                $dw->delete();
            }
            
            $data['count']++;
            
            if ($targetRunTime && microtime(true) - $s > $targetRunTime) {
                $continue = true;
                break;
            }
        }
        
        if (!$continue) {
            return false;
        }
        
        $actionPhrase = new XenForo_Phrase('deleting');
        $typePhrase = new XenForo_Phrase('th_verses_bible');
        $status = sprintf('%s... %s (%s/%s)', $actionPhrase, $typePhrase, XenForo_Locale::numberFormat($data['count']), 
            XenForo_Locale::numberFormat($data['total']));
        
        return $data;
    }

    public function canCancel()
    {
        return true;
    }
}