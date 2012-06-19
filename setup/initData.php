<?php
/**
 * @package modules.seo.setup
 */
class seo_Setup extends object_InitDataSetup
{
	public function install()
	{
		$this->executeModuleScript('lists.xml');
		$this->executeModuleScript('init.xml');
	}

	/**
	 * @return string[]
	 */
	public function getRequiredPackages()
	{
		// Return an array of packages name if the data you are inserting in
		// this file depend on the data of other packages.
		// Example:
		// return array('modules_website', 'modules_users');
		return array();
	}
}