<?php
/**
 * seo_ListRedirectionwebsitesService
 * @package modules.seo.lib.services
 */
class seo_ListRedirectionwebsitesService extends BaseService implements list_ListItemsService
{
	/**
	 * @var seo_ListRedirectionwebsitesService
	 */
	private static $instance;

	/**
	 * @return seo_ListRedirectionwebsitesService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @see list_persistentdocument_dynamiclist::getItems()
	 * @return list_Item[]
	 */
	public final function getItems()
	{
		$domains = array();
		
		$websites = website_WebsiteService::getInstance()->getAll();
		foreach ($websites as $website) 
		{
			if ($website instanceof website_persistentdocument_website) 
			{
				if ($website->getLocalizebypath())
				{
					$domain = $website->getVoDomain();
					if (!in_array($domain, $domains))
					{
						$domains[$website->getId() . '/' . $website->getLang()] = $domain;
					}
				}
				else
				{
					foreach ($website->getI18nInfo()->getLangs() as $lang) 
					{
						$domain = $website->getDomainForLang($lang);
						if (!in_array($domain, $domains))
						{
							$domains[$website->getId() . '/' . $lang] = $domain;
						}
					}
				}
			}	
		}
		
		$items = array();
		foreach ($domains as $value => $label) 
		{
			$items[] = new list_Item($label, $value);
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