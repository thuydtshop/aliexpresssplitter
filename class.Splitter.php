<?php 

class SpitterTools 
{
	public function concatString($strOrg, $strNew, $len = 128)
	{
		$newLen = Tools::strlen($strNew);

		if ($newLen > Tools::strlen($strOrg)) {
			$strOrg = $strNew;
			$return = Tools::substr($strOrg, 0, $len);
		} else {
			$strOrg = Tools::substr($strOrg, 0, $len - $newLen);
			$return = sprintf('%s %s', $strOrg, $strNew);
		}

		return $return;
	}
}