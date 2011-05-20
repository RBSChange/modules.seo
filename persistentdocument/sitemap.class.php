<?php
/**
 * Class where to put your custom methods for document seo_persistentdocument_sitemap
 * @package modules.seo.persistentdocument
 */
class seo_persistentdocument_sitemap extends seo_persistentdocument_sitemapbase 
{
	/**
	 * @return array
	 */
	public function getSitemapUrlInfoArray()
	{
		$str = $this->getSitemapUrlInfo();
		return ($str == null) ?  array() : unserialize($str);
	}

	/**
	 * @param array $array
	 */
	public function setSitemapUrlInfoArray($array)
	{
		if (f_util_ArrayUtils::isEmpty($array))
		{
			$this->setSitemapUrlInfo(null);
		}
		else
		{
			$this->setSitemapUrlInfo(serialize($array));
		}
	}
	
	/**
	 * @return array
	 */
	public function getSitemapExcludedModelsArray()
	{
		$str = $this->getSitemapExcludedModels();
		return ($str == null) ?  array() : explode(',', $str);
	}
	
	public function setSitemapExcludedModelsArray($array)
	{
		if (f_util_ArrayUtils::isEmpty($array))
		{
			$this->setSitemapExcludedModels(null);
		}
		else
		{
			$this->setSitemapExcludedModels(implode(',', $array));
		}
	}
	
	/**
	 * @return string
	 */
	public function getRulesGridJSON()
	{
		$array = $this->getDocumentService()->getRuleGrid($this);
		usort($array, array($this, 'sortRulesGrid'));
		return JsonService::getInstance()->encode($array);
	}
	
	private function sortRulesGrid($a, $b)
	{
		if ($a['status'] === $b['status'])
		{
			if ($a['model'] === $b['model'])
			{
				return 0;
			}
			else
			{
				return $a['model'] > $b['model'] ? 1 : -1;
			}
		}
		else
		{
			return $a['status'] > $b['status'] ? 1 : -1;
		}
	}
	
	/**
	 * @param string $string
	 */
	public function setRulesGridJSON($string)
	{
		$array = JsonService::getInstance()->decode($string);
		$this->getDocumentService()->setRuleGrid($this, $array);
	}
}