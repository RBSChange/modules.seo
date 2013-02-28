<?php
/**
 * seo_GetRewriteruleTemplateVarsByModelNameAction
 * @package modules.seo.actions
 */
class seo_GetRewriteruleTemplateVarsByModelNameAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$result = array();
		
		$modelName = $request->getParameter('modelName');
		$result['templatevars'] = array_values(website_RewriteruleService::getInstance()->getTemplateVarsByModelName($modelName));
		
		return $this->sendJSON($result);
	}
}