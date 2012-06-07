<?php
/**
 * commands_seo_CompileRedirections
 * @package modules.seo.command
 */
class commands_seo_CompileRedirections extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	public function getUsage()
	{
		return "";
	}
	
	/**
	 * @return string
	 */
	public function getDescription()
	{
		return "Compile specific redirection";
	}
	
	/**
	 * @see c_ChangescriptCommand::getEvents()
	 */
	public function getEvents()
	{
		return array(
			array('name' => 'before', 'target' => 'website.compile-htaccess'),
		);
	}

	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	public function _execute($params, $options)
	{
		$this->message("== Compile Redirections ==");
		$this->loadFramework();
		seo_RedirectionService::getInstance()->compileAll();
		$this->quitOk("Command successfully executed");
	}
}