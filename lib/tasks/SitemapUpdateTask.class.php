<?php
class seo_SitemapUpdateTask extends task_SimpleSystemTask
{
	/**
	 * @see task_SimpleSystemTask::execute()
	 */
	protected function execute()
	{
		$sitemap = $this->getSiteMap();
		if ($sitemap)
		{
			$nextRunDate = seo_SitemapService::getInstance()->getNextGenerationDate($sitemap);
			if ($nextRunDate !== null)
			{
				seo_SitemapService::getInstance()->generate($sitemap, $this->plannedTask);
				$this->plannedTask->reSchedule($nextRunDate);
			}
		}
	}
	
	/**
	 * @return seo_persistentdocument_sitemap
	 */
	private function getSiteMap()
	{
		$sitemapId = $this->getParameter('sitemap_id', 0);
		return seo_SitemapService::getInstance()->createQuery()->add(Restrictions::eq('id', $sitemapId))->findUnique();
	}
}