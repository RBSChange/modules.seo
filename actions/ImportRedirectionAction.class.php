<?php
class seo_ImportRedirectionAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		ignore_user_abort();
		set_time_limit(0);
		
		$folderId = $this->getDocumentIdFromRequest($request);
		$websiteAndLang = $request->getParameter('websiteandlang', '');
		list($websiteId, $lang) = explode('/', $websiteAndLang);
		
		if (!count($_FILES))
		{
			return $this->sendJSON(array('message' => LocaleService::getInstance()->transBO('m.seo.bo.actions.file-not-found')));
		}
		$filePath = $_FILES['filename']['tmp_name'];
		$redirectionCount = seo_RedirectionService::getInstance()->importFile($filePath, $websiteId, $lang, $folderId);		
		return $this->sendJSON(array('message' => LocaleService::getInstance()->transBO('m.seo.bo.actions.redirection-imported', 
			array('ucf'), array('redirectionCount' => $redirectionCount))));
	}

	/**
	 * @return Boolean
	 */
	public function isSecure()
	{
		return true;
	}
}