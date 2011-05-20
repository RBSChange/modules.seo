<?php
/**
 * seo_MoveRewriteRuleHereAction
 * @package modules.seo.actions
 */
class seo_MoveRewriteRuleHereAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param ChangeRequest $request
	 */
	public function _execute($context, $request)
	{
		$result = array();
		$folder = generic_persistentdocument_folder::getInstanceById($this->getDocumentIdFromRequest($request));
		$nbRulesMoved = seo_ModuleService::getInstance()->moveRewriteRuleToFolder($folder);
		$this->logAction($folder, array('nbRulesMoved' => $nbRulesMoved));
		return $this->sendJSON(array('folderId' => $folder->getId(), 'rulesMoved' => $nbRulesMoved));
	}	
}