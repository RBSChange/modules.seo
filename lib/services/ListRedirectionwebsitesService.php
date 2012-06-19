<?php
/**
 * @package modules.seo
 * @method seo_ListRedirectionwebsitesService getInstance()
 */
class seo_ListRedirectionwebsitesService extends change_BaseService implements list_ListItemsService
{
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
}