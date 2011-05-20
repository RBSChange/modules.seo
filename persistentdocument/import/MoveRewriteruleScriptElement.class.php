<?php
/**
 * seo_MoveRewriteruleScriptElement
 * @package modules.seo.persistentdocument.import
 */
class seo_MoveRewriteruleScriptElement extends import_ScriptBaseElement
{
	public function endProcess()
	{
		$parent = $this->getParent();
		if ($parent instanceof import_ScriptDocumentElement)
		{
			seo_ModuleService::getInstance()->moveRewriteRuleToFolder($parent->getPersistentDocument());			
		}
	}
}