<?php
/**
 * Class where to put your custom methods for document seo_persistentdocument_redirection
 * @package modules.seo.persistentdocument
 */
class seo_persistentdocument_redirection extends seo_persistentdocument_redirectionbase 
{
	
	/**
	 * @return string
	 */
	public function getWebsiteAndLang()
	{
		if (is_null($this->getWebsite()))
		{
			return null;
		}
		return $this->getWebsite()->getId() . '/' . $this->getWebsiteLang();
	}

	/**
	 * @param string $websiteAndLang
	 */
	public function setWebsiteAndLang($websiteAndLang)
	{
		if (f_util_StringUtils::isEmpty($websiteAndLang) || strpos($websiteAndLang, '/') === false)
		{
			$this->setWebsite(null);
			$this->setWebsiteLang(null);
			return;
		}
		
		list ($wbesiteId, $lang) = explode('/', $websiteAndLang);
		$this->setWebsite(website_persistentdocument_website::getInstanceById($wbesiteId));
		$this->setWebsiteLang($lang);
	}
}