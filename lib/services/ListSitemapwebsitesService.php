<?php
/**
 * seo_ListSitemapwebsitesService
 * @package modules.seo.lib.services
 */
class seo_ListSitemapwebsitesService extends BaseService implements list_ListItemsService
{
	/**
	 * @var seo_ListSitemapwebsitesService
	 */
	private static $instance;

	/**
	 * @return seo_ListSitemapwebsitesService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

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
	
	/**
	 * @see list_persistentdocument_dynamiclist::getItemByValue()
	 * @param string $value;
	 * @return list_Item
	 */
//	public function getItemByValue($value)
//	{
//	}
}