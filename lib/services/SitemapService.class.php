<?php
/**
 * seo_SitemapService
 * @package modules.seo
 */
class seo_SitemapService extends f_persistentdocument_DocumentService
{
	/**
	 * @var seo_SitemapService
	 */
	private static $instance;

	/**
	 * @return seo_SitemapService
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
	 * @return seo_persistentdocument_sitemap
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_seo/sitemap');
	}

	/**
	 * Create a query based on 'modules_seo/sitemap' model.
	 * Return document that are instance of modules_seo/sitemap,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_seo/sitemap');
	}
	
	/**
	 * Create a query based on 'modules_seo/sitemap' model.
	 * Only documents that are strictly instance of modules_seo/sitemap
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_seo/sitemap', false);
	}
	
	/**
	 * @param seo_persistentdocument_sitemap $sitemap
	 * @return string or null
	 */
	public function getNextGenerationDate($sitemap)
	{
		$currentDate = date_Calendar::getInstance();
		$currentDate->setMinute(0);
		$currentDate->setSecond(0);
		
		switch ($sitemap->getGenerationfreq()) 
		{
			case 'daily':
				$currentDate->add(date_Calendar::DAY, 1);
				return $currentDate;
			case 'weekly':
				$currentDate->add(date_Calendar::DAY, 7);
				return $currentDate;
			case 'monthly':
				$currentDate->add(date_Calendar::MONTH, 1);
				return $currentDate;	
		}
		return null;
	}
	
	/**
	 * @param seo_persistentdocument_sitemap $document
	 */
	private function checkGenerationTask($document)
	{
		$freq = $document->getGenerationfreq();
		$taskId = $document->getGenerationTaskId();
		if (intval($taskId))
		{
			$task = task_PlannedtaskService::getInstance()->createQuery()->add(Restrictions::eq('id', $taskId))->findUnique();
			if ($task === null)
			{
				 $document->setGenerationTaskId(null);
				 $taskId = null;
			}
		}
		if ($freq != 'never')
		{
			if ($taskId == null)
			{
				$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
				$parameters = serialize(array('sitemap_id' => $document->getId()));
				$task->setParameters($parameters);
				$task->setSystemtaskclassname('seo_SitemapUpdateTask');
				$task->setNextrundate(date_Calendar::now());
				$task->setLabel($document->getLabel() . ' ' . $freq);
				$task->save(ModuleService::getInstance()->getSystemFolderId('task', 'seo'));
				$document->setGenerationTaskId($task->getId());
			}
			else
			{
				$task->setLabel($document->getLabel() . ' ' . $freq);
				$task->setNextrundate(date_Calendar::now());
				$task->save();
			}
		}
	}
	
	/**
	 * @param seo_persistentdocument_sitemap $sitemap
	 * @param task_persistentdocument_plannedtask $plannedTask
	 * @return integer
	 */
	public function generate($sitemap, $plannedTask = null)
	{
		$nbURLTotal = 0;
		$errors = array();
		$sitemapId = $sitemap->getId();
		$lang = $sitemap->getWebsiteLang();
		$baseURL = 'http://' . $sitemap->getWebsite()->getDomainForLang($lang) . '/';
		
		$batch = f_util_FileUtils::buildRelativePath('modules', 'seo', 'lib', 'bin', 'generateSitemap.php');
		$modelsName = website_UrlRewritingService::getInstance()->getModelNamesAllowURL();
		$excludeModels = $sitemap->getSitemapExcludedModelsArray();
		
		$tmpFile = null; $partURL = 0; $siteMapPart = 0;
		$chunkSize = 100;
		foreach ($modelsName as $modelName) 
		{
			if (in_array($modelName, $excludeModels))
			{
				continue;
			}
			
			$offset = 0;
			do 
			{
				if ($tmpFile === null)
				{
					$tmpFile = f_util_FileUtils::getTmpFile('seo' . $siteMapPart . '_');
					file_put_contents($tmpFile, '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");
				}
				
				if ($plannedTask instanceof task_persistentdocument_plannedtask)
				{
					$plannedTask->ping();
				}
					
				$retVal = f_util_System::execScript($batch, array($sitemapId, $tmpFile, $modelName, $offset, $chunkSize));
				if (strpos($retVal, ',') !== false)
				{
					//Framework::info(__METHOD__ . ' '. $retVal);
					list($result, $nbUrl) = explode(',', $retVal);
					$partURL += $nbUrl;
					$offset += $result;
				}
				else
				{
					if ($plannedTask instanceof task_persistentdocument_plannedtask)
					{
						$errors[] = $result;
					}
					else
					{
						Framework::error(__METHOD__ . ' '. $retVal);
					}
					$result = -1;
				}
				
				if ($partURL > 10000)
				{
					$nbURLTotal += $partURL;
					file_put_contents($tmpFile, "\n</urlset>", FILE_APPEND);
					$this->compressFile($tmpFile, $this->getSitemapPartPath($sitemapId, $siteMapPart));
					
					unlink($tmpFile);
					$tmpFile = null; $partURL = 0; $siteMapPart++; 
				}	
			}
			while ($result == $chunkSize);
		}

		if ($tmpFile !== null)
		{
			$nbURLTotal += $partURL;
			file_put_contents($tmpFile, "\n</urlset>", FILE_APPEND);
			$this->compressFile($tmpFile, $this->getSitemapPartPath($sitemapId, $siteMapPart));
			unlink($tmpFile);
			$tmpFile = null; $partURL = 0; $siteMapPart++; 
		}
		
		if ($plannedTask instanceof task_persistentdocument_plannedtask)
		{
			$plannedTask->ping();
		}
				
		$indexContent = array();
		$indexContent[] = '<?xml version="1.0" encoding="UTF-8"?>';
		$indexContent[] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		for ($i = 0; $i < $siteMapPart; $i++) 
		{
			$indexContent[] = '	<sitemap>';
			$indexContent[] = '		<loc>' .$baseURL. 'sitemap.' .$sitemapId . '.' .$i. '.xml.gz</loc>';
			$indexContent[] = '		<lastmod>' .date('c', date_Calendar::getInstance()->getTimestamp()). '</lastmod>';
			$indexContent[] = '	</sitemap>';
		}
		$indexContent[] = '</sitemapindex>';
		file_put_contents($this->getSitemapIndexPath($sitemapId), implode("\n", $indexContent));	
		if (count($errors))
		{
			throw new Exception(implode("\n", $errors));
		}
		return $nbURLTotal;
	}
	
	/**
	 * 
	 * @param seo_persistentdocument_sitemap $sitemap
	 * @param string $tmpFile
	 * @param string $modelName
	 * @param integer $offset
	 * @param integer $chunkSize
	 */
	public function appendUrl($sitemap, $tmpFile, $modelName, $offset, $chunkSize)
	{
		$filerc = fopen($tmpFile, "a");
		$nbUrl = 0; $chunk = 0;
		$wsurs = website_UrlRewritingService::getInstance();
		try 
		{
			$website = $sitemap->getWebsite();
			$websiteId = $website->getId();
			$lang = $sitemap->getWebsiteLang();
			$baseURL = $wsurs->getRewriteLink($website, $lang, '/')->getUrl();
			$sitemapKey = 'sitemap_' . $websiteId . '/'. $lang;
			
			$modelsInfo = $sitemap->getSitemapUrlInfoArray();
			if (isset($modelsInfo[$modelName]))
			{
				list($changeFreq, $priority) =  $modelsInfo[$modelName];
			}
			else
			{
				$changeFreq = $sitemap->getChangefreq();
				$priority = $sitemap->getPriority();
			}
			
			$documentService = f_persistentdocument_DocumentService::getInstanceByDocumentModelName($modelName);
			if (f_util_ClassUtils::methodExists($documentService, 'getDocumentForSitemap'))
			{
				$documents = $documentService->getDocumentForSitemap($website, $lang, $modelName, $offset, $chunkSize);
				//Framework::info(__METHOD__ . " Specifique $modelName :" . count($documents));
			}
			else
			{
				$query =  $this->pp->createQuery($modelName, false)
								->add(Restrictions::published())
								->addOrder(Order::asc('id'))
								->setFirstResult($offset)->setMaxResults($chunkSize);	
				$documents = $query->find();
			}
			$chunk = count($documents);	
			$ps = f_permission_PermissionService::getInstance();
			foreach ($documents as $document) 
			{
				if ($document instanceof f_persistentdocument_PersistentDocument) 
				{
					$ds = $document->getDocumentService();
					$websiteIds = $ds->getWebsiteIds($document);
					if (is_array($websiteIds) && !in_array($websiteId, $websiteIds))
					{
						continue;
					}
					
					$freq = $changeFreq;
					$prio = $priority;
					if ($document->hasMeta($sitemapKey))
					{
						$sitemapInfo = $document->getMeta($sitemapKey);
						if ($sitemapInfo == 'exclude')
						{
							continue;
						}
						else if ($sitemapInfo != '')
						{
							list ($freq, $prio) = explode(',', $sitemapInfo);
						}
					}
					
					$link = $wsurs->evaluateDocumentLink($document, $website, $lang);
					if ($link !== null)
					{
						$url =  $link->getUrl();
						if (strpos($url, $baseURL) === 0)
						{
							$page = $ds->getDisplayPage($document);
							if ($page)
							{
								$accessorIds = $ps->getAccessorIdsForRoleByDocumentId('modules_website.AuthenticatedFrontUser', $page->getId());
								if (count($accessorIds) == 0)
								{
									
									fwrite($filerc, "\n\t<url>\n\t\t<loc>"  .  htmlspecialchars($url, ENT_COMPAT, 'UTF-8') . "</loc>");
									fwrite($filerc, "\n\t\t<lastmod>"  .  date('c', date_Calendar::getInstance($document->getModificationdate())->getTimestamp()) . "</lastmod>");
									fwrite($filerc, "\n\t\t<changefreq>"  .  $freq . "</changefreq>");
									fwrite($filerc, "\n\t\t<priority>"  .  $prio . "</priority>");
									fwrite($filerc, "\n\t</url>");
									$nbUrl++;
								}
							}
						}
					}
				}

			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		
		fclose($filerc);
		return $chunk . ',' . $nbUrl;
	}
	
	/**
	 * @param integer $sitemapId
	 * @param integer $siteMapPart
	 * @return String
	 */
	private function getSitemapPartPath($sitemapId, $siteMapPart)
	{
		return f_util_FileUtils::buildChangeBuildPath('seo', 'sitemap.' .$sitemapId . '.' .$siteMapPart. '.xml.gz');
	}
	
	/**
	 * @param integer $sitemapId
	 * @return String
	 */
	private function getSitemapIndexPath($sitemapId)
	{
		return f_util_FileUtils::buildChangeBuildPath('seo', 'sitemap_index.' .$sitemapId . '.xml');
	}
	
	/**
	 * @param string $source
	 * @param string $dest
	 */
	private function compressFile($source, $dest)
	{		
		f_util_FileUtils::mkdir(dirname($dest));
		$fp_out = gzopen($dest, 'w9');
		if ($fp_out)
		{
			$fp_in = fopen($source, 'rb');
			if ($fp_in)
			{
				while (! feof($fp_in))
				{
					gzwrite($fp_out, fread($fp_in, 1024 * 512));
				}
				fclose($fp_in);
			}
			gzclose($fp_out);
		}
	}
	
	/**
	 * @param seo_persistentdocument_sitemap $sitemap
	 * @return string or null
	 */	
	public function getIndexURL($sitemap)
	{
		$website = $sitemap->getWebsite();
		$lang = $sitemap->getWebsitelang();
		if ($website !== null && $website->isLangAvailable($lang))
		{
			return "http://" . $website->getDomainForLang($lang) . "/sitemap_index."  . $sitemap->getId() . ".xml"; 
		}
		return null;
	}
	
	
	
	
	/**
	 * @param seo_persistentdocument_sitemap $sitemap
	 * @return string
	 */
	public function getIndexFilePath($sitemap)
	{
		$path = $this->getSitemapIndexPath($sitemap->getId());
		return (is_readable($path)) ? $path : null;
	}
	
	/**
	 * @param seo_persistentdocument_sitemap $sitemap
	 * @param integer $index
	 * @return string
	 */
	public function getPathPart($sitemap, $index)
	{
		$path = $this->getSitemapPartPath($sitemap->getId(), $index);
		return (is_readable($path)) ? $path : null;
	}	
	
	/**
	 * @return website_persistentdocument_website[]
	 */
	public function getWebsiteAvailable()
	{
		$result = array();
		$websites = website_WebsiteService::getInstance()->getAll();
		foreach ($websites as $website) 
		{
			if (count($this->getAvailableLangs($website)) > 0)
			{
				$result[] = $website;
			}
		}
		return $result;
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @return string[];
	 */
	private function getAvailableLangs($website)
	{
		$langs = $website->getI18nInfo()->getLangs();
		$definedLangs = $this->createQuery()
			->add(Restrictions::eq('website', $website))
			->setProjection(Projections::property('websiteLang', 'websiteLang'))
			->findColumn('websiteLang');
		return array_diff($langs, $definedLangs);	
	}
	
	/**
	 * @param seo_persistentdocument_sitemap $document (Read only)
	 * @param array $defaultSynchroConfig string : string[]
	 * @return array string : string[]
	 */
	public function getI18nSynchroConfig($document, $defaultSynchroConfig)
	{
		return array();
	}
	
	/**
	 * @param seo_persistentdocument_sitemap $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		$website = $document->getWebsite();
		if ($document->getWebsiteLang() == null)
		{
			$document->setWebsiteLang(f_util_ArrayUtils::firstElement($this->getAvailableLangs($website)));
		}
		if ($document->getLabel() == null)
		{
			$lang = $document->getWebsiteLang();
			$document->setLabel($website->getLabelForLang($lang) . ' (' . $lang . ')');
		}
	}



	/**
	 * @param seo_persistentdocument_sitemap $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postInsert($document, $parentNodeId)
	{
		$website = $document->getWebsite();
		$langs = $this->getAvailableLangs($website);
		if (count($langs))
		{
			$lang = $lang[0];
			$sitemap = $this->getNewDocumentInstance();
			$sitemap->setWebsite($website);
			$sitemap->setWebsiteLang($lang);
			$this->save($sitemap, $parentNodeId);
		}
	}

	/**
	 * @param seo_persistentdocument_sitemap $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function preUpdate($document, $parentNodeId)
	{
		if ($document->isPropertyModified('generationfreq'))
		{
			$this->checkGenerationTask($document);
		}
	}
	
	/**
	 * @param seo_persistentdocument_sitemap $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$resume = parent::getResume($document, $forModuleName, $allowedSections);
		$baseURL = website_UrlRewritingService::getInstance()->getRewriteLink($document->getWebsite(), $document->getWebsiteLang(), '/');
		$resume['properties']['baseURL'] = $baseURL->getUrl();
		$resume['properties']['indexURL'] = $this->getIndexURL($document);
		$statusKey  = 'm.seo.bo.actions.' . ($this->getIndexFilePath($document) !== null ? 'refresh' : 'create');
		$resume['properties']['generate'] = LocaleService::getInstance()->transBO($statusKey, array('ucf', 'html'));
		return $resume;
	}
	
	/**
	 * @param seo_persistentdocument_sitemap $document
	 * @return array<status,module,doclabel,model,changefreq,priority,actionrow>
	 */
	public function getRuleGrid($document)
	{
		$modelsName = website_UrlRewritingService::getInstance()->getModelNamesAllowURL();
		$ecludeModels = $document->getSitemapExcludedModelsArray();
		$modelsInfo = $document->getSitemapUrlInfoArray();
		$defFreq = $document->getChangefreq();
		$defPrio = $document->getPriority();
		$result = array();
		foreach ($modelsName as $modelName) 
		{
			$infos = f_persistentdocument_PersistentDocumentModel::getModelInfo($modelName);
			$doclabel = LocaleService::getInstance()->transBO('m.' . $infos['module'] . '.document.'. $infos['document'] . '.document-name');
			$row = array('status' => 'active', 'model' => $modelName, 'doclabel' => $doclabel, 
				'changefreq' => $defFreq, 'priority' => $defPrio, 'module' => $infos['module']);
			
			if (in_array($modelName, $ecludeModels))
			{
				$row['status'] = 'exclude';
			}
			else if (isset($modelsInfo[$modelName]))
			{
				$row['changefreq'] = $modelsInfo[$modelName][0];
				$row['priority'] = $modelsInfo[$modelName][1];
			}
			$result[] = $row;
		}
		return $result;
	}
	
	/**
	 * @param seo_persistentdocument_sitemap $document
	 * @param array $array
	 */
	public function setRuleGrid($document, $array)
	{
		$defFreq = $document->getChangefreq();
		$defPrio = $document->getPriority();
		$ecludeModels = array();
		$modelsInfo = array();
		foreach ($array as $row) 
		{
			if ($row['status'] == 'exclude')
			{
				$ecludeModels[] = $row['model'];
			}
			else if ($row['changefreq'] != $defFreq || $row['priority'] != $defPrio)
			{
				$modelsInfo[$row['model']] = array($row['changefreq'], $row['priority']);
			}
		}
		$document->setSitemapExcludedModelsArray($ecludeModels);
		$document->setSitemapUrlInfoArray($modelsInfo);
	}
}