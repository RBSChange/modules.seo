<?php
/**
 * @package modules.seo.setup
 */
class seo_Setup extends object_InitDataSetup
{
	public function install()
	{
		$this->executeModuleScript('init.xml');
	}
}