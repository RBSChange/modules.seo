<?php
/**
 * @package modules.seo
 * @method seo_ListSitemapwebsitesService getInstance()
 */
class seo_ListSitemapwebsitesService extends change_BaseService implements list_ListItemsService
{
	/**
	 * @see list_persistentdocument_dynamiclist::getItems()
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		$items = array();
		$websites = seo_SitemapService::getInstance()->getWebsiteAvailable();
		foreach ($websites as $website) 
		{
			$items[] = new list_Item($website->getTreeNodeLabel(), $website->getId());
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