<?php
/**
 * seo_SitemapScriptDocumentElement
 * @package modules.seo.persistentdocument.import
 */
class seo_SitemapScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return seo_persistentdocument_sitemap
	 */
	protected function initPersistentDocument()
	{
		return seo_SitemapService::getInstance()->getNewDocumentInstance();
	}
	
	/**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_seo/sitemap');
	}
}