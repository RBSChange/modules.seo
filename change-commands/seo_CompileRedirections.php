<?php
/**
 * commands_seo_CompileRedirections
 * @package modules.seo.command
 */
class commands_seo_CompileRedirections extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 * @example "<moduleName> <name>"
	 */
	function getUsage()
	{
		return "";
	}
	

	/**
	 * @return String
	 * @example "initialize a document"
	 */
	function getDescription()
	{
		return "Compile specific redirection";
	}
	
	
	
	/**
	 * @see c_ChangescriptCommand::isHidden()
	 */
	public function isHidden()
	{
		return true;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Compile Redirections ==");
		$this->loadFramework();
		seo_RedirectionService::getInstance()->compileAll();
		$this->quitOk("Command successfully executed");
	}
}