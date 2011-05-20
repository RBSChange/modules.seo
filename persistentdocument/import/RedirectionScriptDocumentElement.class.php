<?php
/**
 * seo_RedirectionScriptDocumentElement
 * @package modules.seo.persistentdocument.import
 */
class seo_RedirectionScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return seo_persistentdocument_redirection
     */
    protected function initPersistentDocument()
    {
    	return seo_RedirectionService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_seo/redirection');
	}
}