<?php
/**
 * @package modules.seo
 * @method seo_ModuleService getInstance()
 */
class seo_ModuleService extends ModuleBaseService
{
	/**
	 * @param generic_persistentdocument_folder $folder
	 * @return integer
	 */
	public function moveRewriteRuleToFolder($folder)
	{
		$nbMoved = 0;
		$treeId = $folder->getTreeId();
		if ($treeId == null)
		{
			return $nbMoved;
		}
		
		$ts = TreeService::getInstance();
		$ts->setTreeNodeCache(false);
		
		$rules = website_RewriteruleService::getInstance()->createQuery()->find();
		foreach ($rules as $rule) 
		{
			if ($rule instanceof website_persistentdocument_rewriterule) 
			{
				if ($rule->getTreeId() != $treeId)
				{
					if ($rule->getTreeId() !== null)
					{
						$node = $ts->getInstanceByDocument($rule);
						$ts->deleteNode($node);
					}
					$ts->newLastChild($folder->getId(), $rule->getId());
					$nbMoved++;
				}
				else
				{
					$node = $ts->getInstanceByDocument($rule);
					if ($node->getParentId() != $folder->getId())
					{
						$ts->moveToLastChild($rule->getId(), $folder->getId());
						$nbMoved++;
					}
				}
			}
		}
		return $nbMoved;
	}
	
	/**
	 * @param string $contents
	 * @param website_persistentdocument_website $website
	 */
	public function appendRobotTxtContent($contents, $website)
	{
		$sitemaps = seo_SitemapService::getInstance()->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::eq('website', $website))
			->find();
			
		foreach ($sitemaps as $sitemap) 
		{
			if ($sitemap instanceof seo_persistentdocument_sitemap) 
			{
				$indexURL = $sitemap->getDocumentService()->getIndexURL($sitemap);
				if ($indexURL)
				{
					$contents .= "\nSitemap: $indexURL";
				}
			}
		}
		return $contents;
	}
}