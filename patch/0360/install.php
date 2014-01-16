<?php
/**
 * seo_patch_0360
 * @package modules.seo
 */
class seo_patch_0360 extends patch_BasePatch
{
//  by default, isCodePatch() returns false.
//  decomment the following if your patch modify code instead of the database structure or content.
    /**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
//	public function isCodePatch()
//	{
//		return true;
//	}
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->execChangeCommand('compile-documents');

		// Add property 'includeInRobotsTxt' on document sitemap
		$newPath = f_util_FileUtils::buildWebeditPath('modules/seo/persistentdocument/sitemap.xml');
		$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'seo', 'sitemap');
		$newProp = $newModel->getPropertyByName('includeinrobotstxt');
		f_persistentdocument_PersistentProvider::getInstance()->addProperty('seo', 'sitemap', $newProp);

		// Init default value (includeInRobotsTxt --> true) for existing sitemaps 
		$query = "UPDATE `m_seo_doc_sitemap` set `includeinrobotstxt` = 1";
		$result = $this->executeSQLQuery($query);
		$this->log($result . ' Documents seo/sitemap updated');	
	
	}
}