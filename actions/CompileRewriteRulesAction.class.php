<?php
/**
 * seo_CompileRewriteRulesAction
 * @package modules.seo.actions
 */
class seo_CompileRewriteRulesAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();
		$result['nbRules'] = website_UrlRewritingService::getInstance()->buildRules();
		return $this->sendJSON($result);
	}
}