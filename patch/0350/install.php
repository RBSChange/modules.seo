<?php
/**
 * seo_patch_0350
 * @package modules.seo
 */
class seo_patch_0350 extends patch_BasePatch
{
	
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		if (ModuleService::getInstance()->moduleExists('referencing'))
		{
			$srs = seo_RedirectionService::getInstance();
			
			$this->log('Remove old referencing htaccess rules');
			
			$content = "# Rules for OLD REFERENCING.\n";
			$apacheService = ApacheService::getInstance();
			$apacheService->generateSpecificConfFileForModule('referencing', $content);
			
			$redirections = referencing_RedirectionService::getInstance()->createQuery()->find();
			$rootFolder = generic_persistentdocument_rootfolder::getInstanceById(
					ModuleService::getInstance()->getRootFolderId('seo'));
			$rFolder = generic_FolderService::getInstance()->createQuery()->add(
					Restrictions::eq('label', 'Redirections'))->add(
					Restrictions::childOf($rootFolder->getId()))->findUnique();
			
			if ($rFolder === null)
			{
				$rFolder = generic_FolderService::getInstance()->getNewDocumentInstance();
				$rFolder->setLabel('Redirections');
				$rFolder->setDescription('Redirections importé de l\'ancien module referencing');
				$rFolder->save($rootFolder->getId());
			}
			
			foreach ($redirections as $redirection)
			{
				try
				{
					$this->beginTransaction();
					
					if ($redirection instanceof referencing_persistentdocument_redirection)
					{
						$this->log('migrate redirection : ' . $redirection->getLabel() . ' / ' . $redirection->getId());
						
						$website = $redirection->getWebsite();
						if ($website->getLocalizebypath())
						{
							$langs = array($website->getLang());
						}
						else
						{
							$langs = $website->getI18nInfo()->getLangs();
						}
						
						foreach ($langs as $lang)
						{
							$parentId = null;
							$nred = $srs->getByOldpath($website, $lang, $redirection->getOldUrl());
							if ($nred === null)
							{
								$nred = $srs->getNewDocumentInstance();
								$parentId = $rFolder->getId();
							}
							$nred->setOldUrl($redirection->getOldUrl());
							$nred->setNewUrl($redirection->getNewUrl());
							$nred->setWebsite($website);
							$nred->setWebsiteLang($lang);
							$nred->save($parentId);
						}
					}
					
					$this->commit();
				}
				catch (Exception $e)
				{
					$this->rollBack($e);
				}
			
			}
			
			$this->log('compile htaccess');
			$srs->compileAll();
			ApacheService::getInstance()->compileHtaccess();
			
			
			$urlRewritingInfos = referencing_UrlrewritinginfoService::getInstance()->createQuery()->find();		
			foreach ($urlRewritingInfos as $urlRewritingInfo)
			{
				$package = $urlRewritingInfo->getPackage();
				$moduleName = ModuleService::getInstance()->getShortModuleName($package);
				$xml = $urlRewritingInfo->getContent();
				$configFilePath = f_util_FileUtils::buildOverridePath('modules', $moduleName, 'config', 'urlrewriting.xml');
				if (!file_exists($configFilePath))
				{
					$this->log('flush: ' . $configFilePath);
					f_util_FileUtils::writeAndCreateContainer($configFilePath, $xml);
				}
			}
		}
		
		if (ModuleService::getInstance()->moduleExists('compatibilityos'))
		{
			foreach (ModuleService::getInstance()->getModulesObj() as $cModule) 
			{
				if ($cModule instanceof c_Module)
				{
					$moduleName = $cModule->getName();
					if ($moduleName == 'compatibilityos') {continue;}	
					$configFilePath = f_util_FileUtils::buildOverridePath('modules', $moduleName, 'config', 'urlrewriting.xml');
					if (!file_exists($configFilePath))
					{
						$srcXml = f_util_FileUtils::buildWebeditPath('modules', 'compatibilityos', 'override', $moduleName, 'config', 'urlrewriting.xml');
						if (file_exists($srcXml))
						{
							$this->log('cp: ' . $srcXml . ' -> ' . $configFilePath);
							f_util_FileUtils::cp($srcXml, $configFilePath);
						}
					}
				}
			}
		}
		
		$this->log('compile-url-rewriting ....');
		$this->execChangeCommand('compile-url-rewriting');
	}
}