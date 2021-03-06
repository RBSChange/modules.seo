<?php
/**
 * seo_GetSitemapAction
 * @package modules.seo.actions
 */
class seo_GetSitemapAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param ChangeRequest $request
	 */
	public function _execute($context, $request)
	{
		$path = null;
		try 
		{
			$sitemap = seo_persistentdocument_sitemap::getInstanceById($this->getDocumentIdFromRequest($request));
			$sms = $sitemap->getDocumentService();
			if ($request->hasParameter('sitemapindex'))
			{
				$path = $sms->getIndexFilePath($sitemap);
			}
			else if ($request->hasParameter('index'))
			{
				$path = $sms->getPathPart($sitemap, intval($request->getParameter('index')));
			}
		} 
		catch (Exception $e) 
		{
			Framework::exception($e);
			$path = null;
		}
		
		if ($path === null)
		{
			$context->getController()->forward('website', 'Error404');
			return View::NONE;
		}
		
		if (f_util_StringUtils::endsWith($path, '.xml', f_util_StringUtils::CASE_SENSITIVE))
		{
			header('Content-type: text/xml');
		}
		else
		{
			header('Content-type: application/octet-stream');
		}
		header('Content-length: '.filesize($path));
		readfile($path);
		return View::NONE;
	}
	
	/**
	 * @see f_action_BaseAction::isSecure()
	 */
	public function isSecure()
	{
		return false;
	}
}