<?php
/**
 * seo_CompileHtAccessAction
 * @package modules.seo.actions
 */
class seo_CompileHtAccessAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
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