<?php
/**
 * commands_seo_GenerateSitemaps
 * @package modules.seo.command
 */
class commands_seo_GenerateSitemaps extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}

	/**
	 * @return String
	 */
	function getDescription()
	{
		return "generate all published sitmap";
	}
	

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Generate sitemaps ==");
		$this->loadFramework();
		$ssms = seo_SitemapService::getInstance();
		$sitemaps = $ssms->createQuery()->add(Restrictions::published())->find();

		foreach ($sitemaps as $sitemap) 
		{
			$this->message("Generate site map:  ". $sitemap->getLabel());
			$nbUrl = $ssms->generate($sitemap);
			$this->message($sitemap->getLabel() . " generated with " . $nbUrl . " URL");
		}
		$this->okMessage("Site map files successfully generated");
	}
}