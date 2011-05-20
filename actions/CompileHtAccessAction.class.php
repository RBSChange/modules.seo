<?php
/**
 * seo_CompileHtAccessAction
 * @package modules.seo.actions
 */
class seo_CompileHtAccessAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param ChangeRequest $request
	 */
	public function _execute($context, $request)
	{
		$result = array();
		seo_RedirectionService::getInstance()->compileAll();
		ApacheService::getInstance()->compileHtaccess();
		$result['ok'] = true;
		return $this->sendJSON($result);
	}
}