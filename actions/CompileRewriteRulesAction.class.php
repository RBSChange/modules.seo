<?php
/**
 * seo_CompileRewriteRulesAction
 * @package modules.seo.actions
 */
class seo_CompileRewriteRulesAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param ChangeRequest $request
	 */
	public function _execute($context, $request)
	{
		$result = array();
		$result['nbRules'] = website_UrlRewritingService::getInstance()->buildRules();
		return $this->sendJSON($result);
	}
}