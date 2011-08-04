<?php
/**
 * seo_GenerateSitemapAction
 * @package modules.seo.actions
 */
class seo_GenerateSitemapAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$sitemap = seo_persistentdocument_sitemap::getInstanceById($this->getDocumentIdFromRequest($request));
		$nburl = $sitemap->getDocumentService()->generate($sitemap);	
		$this->logAction($sitemap, array('nburl' => $nburl));
		return $this->sendJSON(array('nburl' => $nburl));
	}
}