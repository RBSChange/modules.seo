<?php
/**
 * @package modules.seo
 * @method seo_ListNewmodelruleService getInstance()
 */
class seo_ListNewmodelruleService extends change_BaseService implements list_ListItemsService
{
	/**
	 * @see list_persistentdocument_dynamiclist::getItems()
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		$wrrs = website_RewriteruleService::getInstance();
		$items = array();
		$modelNames = website_UrlRewritingService::getInstance()->getModelNamesAllowURL();
		foreach ($modelNames as $modelName) 
		{
			$rule = $wrrs->getByModelName($modelName);
			if ($rule === null)
			{
				$baseKey = str_replace(array('modules_', '/'), array('m.', '.document.'), $modelName);
				$label = LocaleService::getInstance()->trans($baseKey . '.document-name', array('html'));
				
				$keyParts = explode('.', $baseKey);
				$module =  LocaleService::getInstance()->trans('m.'.$keyParts[1].'.bo.general.module-name' , array('html'));
				
				$items[] = new list_Item($module . ' / ' . $label, $modelName);
			}
		}
		return $items;
	}

	/**
	 * @var Array
	 */
	private $parameters = array();
	
	/**
	 * @see list_persistentdocument_dynamiclist::getListService()
	 * @param array $parameters
	 */
	public function setParameters($parameters)
	{
		$this->parameters = $parameters;
	}
}