<?php

/**
 * @author    Markus Lehmann (mar_lehmann@hotmail..com)
 * @copyright Copyright2016
 * @version   1.0
 * @since     03.09.16
 */
class cf_oxid_data_provider
{

    protected $aTypes;

    protected $aBlocks;


    public function getType(&$sClass)
    {
        if (isset($this->aTypes[$sClass])) {
            $sResult = $this->aTypes[$sClass];
        }
        else {
            $sResult = $this->guessValue($this->aTypes, $sClass, 'Typ');
        }

        return $sResult;
    }


    public function getBlockPath(&$sBlock)
    {
        if (isset($this->aBlocks[$sBlock])) {
            $aResult = $this->aBlocks[$sBlock];
            $dCount = count($aResult);
            if ($dCount > 1) {
                $sResult = $this->getValueFromUser($aResult, 'Block', $dCount);
            }
            else {
                $sResult = current($aResult);
            }
        }
        else {
            $sResult = $this->guessValue($this->aBlocks, $sBlock, 'Block');
        }

        return $sResult;
    }


    public function getBlockPaths($sBlock)
    {
        $aResult = array();

        if (isset($this->aBlocks[$sBlock])) {
            $aResult = $this->aBlocks[$sBlock];
        }

        return $aResult;
    }


    public function hasType($sClass)
    {
        return isset($this->aTypes[$sClass]);
    }


    public function hasBlock($sBlock)
    {
        return isset($this->aBlocks[$sBlock]);
    }


    public function loadTypes()
    {
        $this->aTypes = $this->getClasses($this->aTypes, getShopBasePath() . "application");
        $this->aTypes = $this->getClasses($this->aTypes, getShopBasePath() . "core", "core");
    }


    protected function getClasses($aTypes, $sPath, $sType = "")
    {
        $dirHandle = opendir($sPath);
        while ($sClass = readdir($dirHandle)) {
            if ($sClass != '.' && $sClass != '..') {
                if (substr($sClass, -4) == '.php') {
                    $aTypes[substr($sClass, 0, -4)] = $sType;
                }
                else if ($sClass != 'views' && $sClass != 'translations' && is_dir($sPath . '/' . $sClass)) {
                    $sNewType = strlen($sType) == 0 ? $sClass : $sType . "/" . $sClass;
                    $aTypes = $this->getClasses($aTypes, $sPath . '/' . $sClass, $sNewType);
                }
            }
        }
        closedir($dirHandle);

        return $aTypes;
    }


    public function loadBlocks()
    {
        $sViewPath = getShopBasePath() . "application/views";
        $this->aBlocks = $this->getBlocks($sViewPath);
    }


    protected function getBlocks($sPath, $sType = null, $aBlocks = array())
    {
        $dirHandle = opendir($sPath);
        while ($sFile = readdir($dirHandle)) {
            if ($sFile != '.' && $sFile != '..') {
                if (substr($sFile, -4) == '.tpl') {
                    $sTmpType = isset($sType) ? $sType . '/' . $sFile : $sFile;
                    $aBlocks = $this->parseBlocks($sPath . '/' . $sFile, $sTmpType, $aBlocks);
                }
                else if (is_dir($sPath . '/' . $sFile)) {
                    if ($sFile == 'tpl') {
                        $sTmpType = null;
                    }
                    else if (!isset($sType)) {
                        $sTmpType = $sFile;
                    }
                    else {
                        $sTmpType = $sType . '/' . $sFile;
                    }
                    $aBlocks = $this->getBlocks($sPath . '/' . $sFile, $sTmpType, $aBlocks);
                }
            }
        }
        closedir($dirHandle);

        return $aBlocks;
    }


    protected function parseBlocks($sFilePath, $sType, $aBlocks)
    {
        $sContent = file_get_contents($sFilePath);
        $iLimit = 500;
        while (--$iLimit && preg_match('/\[\{\s*block\s+name\s*=\s*([\'"])([a-z0-9_]+)\1\s*\}\](.*?)\[\{\s*\/block\s*\}\]/is', $sContent, $m)) {
            $sBlock = $m[0];
            $sBlockName = $m[2];
            $sBlockContent = $m[3];
            if (preg_match('/^.+(\[\{\s*block\s+name\s*=\s*([\'"])([a-z0-9_]+)\2\s*\}\](.*?)\[\{\s*\/block\s*\}\])$/is', $sBlock, $m)) {
                // shift to (deepest) nested tag opening
                $sBlock = $m[1];
                $sBlockName = $m[3];
                $sBlockContent = $m[4];
            }
            $sContent = str_replace($sBlock, $sBlockContent, $sContent);
            $aBlocks[$sBlockName][$sType] = $sType;
        }

        return $aBlocks;
    }


    /**
     * @param $aHaystack
     * @param $sNeedle
     * @param $sType
     *
     * @return null
     */
    protected function guessValue($aHaystack, &$sNeedle, $sType)
    {
        $aKeys = array_keys($aHaystack);
        $aResult = array_filter($aKeys, function ($element) use ($sNeedle) {
            return preg_match('/^' . $sNeedle . '.*/', $element);
        });
        $dCount = count($aResult);
        if ($dCount == 1) {
            $sNeedle = current($aResult);
            $sResult = $aHaystack[$sNeedle];
        }
        else if ($dCount == 0) {
            $sResult = null;
        }
        else {
            $sNeedle = $this->getValueFromUser($aResult, $sType, $dCount);
            $sResult = $aHaystack[$sNeedle];
        }

        return $sResult;
    }


    protected function getValueFromUser($aValues, $sType, $dCount)
    {
        $aValues = array_values($aValues);
        $sHelpText = 'Der ' . $sType . ' ist nicht eindeutig. Bitte Nummer wählen:' . PHP_EOL;
        foreach ($aValues as $iKey => $sResult) {
            $sHelpText .= ($iKey + 1) . ". " . $sResult . PHP_EOL;
        }
        $iIndex = 0;
        while ($iIndex <= 0 || $iIndex > $dCount) {
            echo $sHelpText;
            $iIndex = intval(trim(fgets(STDIN)));
        }

        return $aValues[($iIndex - 1)];
    }
}
