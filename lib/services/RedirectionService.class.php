<?php
/**
 * seo_RedirectionService
 * @package modules.seo
 */
class seo_RedirectionService extends f_persistentdocument_DocumentService
{
	/**
	 * @var seo_RedirectionService
	 */
	private static $instance;

	/**
	 * @return seo_RedirectionService
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
	 * @return seo_persistentdocument_redirection
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_seo/redirection');
	}

	/**
	 * Create a query based on 'modules_seo/redirection' model.
	 * Return document that are instance of modules_seo/redirection,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_seo/redirection');
	}
	
	/**
	 * Create a query based on 'modules_seo/redirection' model.
	 * Only documents that are strictly instance of modules_seo/redirection
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_seo/redirection', false);
	}
	
	public function compileAll()
	{
		try 
		{
			$content = "# Rules for SEO redirections.\n" . $this->generateHtAccessRules();
			$apacheService = ApacheService::getInstance();
			$apacheService->generateSpecificConfFileForModule('seo', $content);
		} 
		catch (Exception $e) 
		{
			Framework::exception($e);
		}
	}
	
	/**
	 * @param website_persistentdocument_website $website
	 * @param string lang
	 * @param string $oldPath
	 * @return seo_persistentdocument_redirection or null
	 */
	public function getByOldpath($website, $lang, $oldPath)
	{
		return $this->createQuery()
			->add(Restrictions::eq('oldUrl', $oldPath))
			->add(Restrictions::eq('website', $website))
			->add(Restrictions::eq('websiteLang', $lang))
			->findUnique();
	}
	
	/**
	 * @return void
	 */
	private function generateHtAccessRules()
	{
		// Write redirections file.
		$data = array();
		$website = null;
		$rqc = RequestContext::getInstance();
		foreach ($this->createQuery()->add(Restrictions::published())->find() as $redirection)
		{
			if ($redirection instanceof seo_persistentdocument_redirection)
			{
				$website = $redirection->getWebsite();
				$lang = $redirection->getWebsiteLang();
				if (!$website->isLangAvailable($lang)) {continue;}
				$domain = preg_quote($website->getDomainForLang($lang));
				$data[] = 'RewriteCond %{SERVER_NAME} ^(www\.)?(' .$domain.')$ [NC]';
				
				$oldUrl = $redirection->getOldUrl();
				if ($oldUrl[0] == '/'){$oldUrl = substr($oldUrl, 1);}
				$newUrl = $redirection->getNewUrl();
				
				// For pages with variables, we need:
				// * a specific condition to check the query string.
				// * a '?' in trhe rewrite rule after the new URL to remove the query string.
				if (strpos($oldUrl, '?') !== false)
				{
					list($oldUrl, $queryString) = explode('?', $oldUrl);
					$data[] = 'RewriteCond %{QUERY_STRING} ^'.preg_quote($queryString).'$';
					$data[] = 'RewriteRule ^' . preg_quote($oldUrl) . '$ ' . $newUrl . ' [L,R=301]';
				}
				else 
				{
					$data[] = 'RewriteRule ^' . preg_quote($oldUrl) . '$ ' . $newUrl . ' [L,R=301]';
				}
			}
		}
		return join("\n", $data);
	}
	
	/**
	 * @param seo_persistentdocument_redirection $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
	protected function preSave($document, $parentNodeId)
	{
		if ($document->getLabel() == null)
		{
			$document->setLabel($document->getNewUrl());
		}
	}
	
	
	/**
	 * @param seo_persistentdocument_redirection $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
	public function getResume($document, $forModuleName, $allowedSections = null)
	{
		$resume = parent::getResume($document, $forModuleName, $allowedSections);
		$resume['properties']['compile'] = '...';
		$resume['properties']['newURL'] = $document->getNewUrl();
		$website = $document->getWebsite();
		$domain = $website->getDomainForLang($document->getWebsiteLang());
		$resume['properties']['fromURL'] = 'http://' . $domain . $document->getOldUrl();
		return $resume;
	}

	/**
	 * @param string $filePath
	 * @param integer $websiteId
	 * @param string $lang
	 * @param integer $folderId
	 * @return integer
	 */
	public function importFile($filePath, $websiteId, $lang, $folderId)
	{
		$nbImport = 0;
		$website = website_persistentdocument_website::getInstanceById($websiteId);
		
		$handle = fopen($filePath, 'r');
		while (($data = fgetcsv($handle, 2048, ';')) !== false)
		{
			 if (count($data) == 2)
			 {
					$oldPath = trim($data[0]);
					$newUrl = trim($data[1]);
					if (f_util_StringUtils::isEmpty($oldPath) ||  f_util_StringUtils::isEmpty($newUrl)) 
					{ 
						Framework::error(__METHOD__ . ' Empty url');
						continue;
					}
					if (strlen($oldPath) > 254 || strlen($newUrl) > 254)
					{
						Framework::error(__METHOD__ . ' too long url');
						continue;
					}
					if (preg_match('/[\s#]+/', $oldPath) || preg_match('/[\s]+/', $newUrl))
					{
						Framework::error(__METHOD__ . ' Invalid caracters');
						continue;
					}
					if (strpos($newUrl, 'http://') !== 0 && strpos($newUrl, 'https://') !== 0)
					{
						Framework::error(__METHOD__ . ' Invalid new url protocol');
						continue;
					}
					if (count(explode('?', $oldPath)) > 2 || count(explode('?', $newUrl)) > 2)
					{
						Framework::error(__METHOD__ . ' Invalid Query string');
						continue;
					}
					$redirection = $this->getByOldpath($website, $lang, $oldPath);
					if ($redirection === null)
					{
						$redirection =$this->getNewDocumentInstance();
					}
					else
					{
						Framework::info(__METHOD__ . ' Redefine: ' . $redirection->getId());
					}
					$redirection->setWebsite($website);
					$redirection->setWebsiteLang($lang);
					$redirection->setOldUrl($oldPath);
					$redirection->setNewUrl($newUrl);
					$this->save($redirection, $folderId);
					$nbImport++;
					
			 }
		}
		return $nbImport;
	}	
}