<?php

/**
 * @author    Markus Lehmann (mar_lehmann@hotmail.com)
 * @copyright Copyright2016, Markus Lehmann
 * @version   1.0
 * @since     21.08.16
 */
class cf_generator
{

    protected $aTypes;


    public function __construct($sModule, $sClass = null)
    {
        if (!isset($sClass)) {
            $this->generateModule($sModule);
        }
        else {
            $this->loadTypes();
            $sType = $this->getType($sClass);
            $this->generateType($sType, $sModule, $sClass);
        }
    }


    protected function generateModule($sModule)
    {
        $blResult = false;
        $sModulePath = getShopBasePath() . "modules/$sModule";
        if (!file_exists($sModulePath)) {
            mkdir($sModulePath);
            $this->writeFile($sModulePath . "/metadata.php", $this->getMetaDataContent($sModule));
            $blResult = true;
        }

        return $blResult;
    }


    /**
     * @param $sType
     * @param $sModule
     * @param $sClass
     */
    protected function generateType($sType, $sModule, $sClass)
    {
        $sModulePath = getShopBasePath() . "modules/$sModule";

        if ($this->checkModule($sModule)) {

            $sTypePath = $sModulePath . "/$sType";

            if (!file_exists($sTypePath)) {
                mkdir($sTypePath, 0777, true);
            }

            if (file_exists($sTypePath) && is_dir($sTypePath) && is_writable($sTypePath)) {
                $sFilePath = $sTypePath . "/{$sModule}__{$sClass}.php";
                $sContent = $this->getClassContent($sModule, $sClass);
                $this->writeFile($sFilePath, $sContent);
                $this->updateMetadata($sType, $sModule, $sClass);
            }
            else {
                die("Typpfad existiert nicht oder ist nicht schreibbar" . PHP_EOL);
            }
        }
        else {
            die("Modulpfad existiert nicht oder ist nicht schreibbar: $sModulePath" . PHP_EOL);
        }
    }


    protected function checkModule($sModule)
    {
        $sModulePath = getShopBasePath() . "modules/$sModule";

        $this->generateModule($sModule);

        return file_exists($sModulePath) && is_dir($sModulePath) && is_writable($sModulePath);
    }


    protected function writeFile($sFilePath, $sContent)
    {
        $oFile = fopen($sFilePath, "w");
        fwrite($oFile, $sContent);
        fclose($oFile);
    }


    /**
     * @param $sModule
     * @param $sClass
     *
     * @return string
     */
    protected function getClassContent($sModule, $sClass)
    {
        $sSignature = $this->getSignatureContent($sModule);
        $sContent = <<<HEREDOC
<?php

$sSignature
 
class {$sModule}__{$sClass} extends {$sModule}__{$sClass}_parent {

}
HEREDOC;

        return $sContent;
    }


    /**
     * @param $sModule
     *
     * @return string
     *
     */
    protected function getMetaDataContent($sModule)
    {
        $sSignature = $this->getSignatureContent($sModule);
        $sVersion = $this->getVersion();
        $sAuthor = $this->getAuthor();
        $sUrl = $this->getUrl();
        $sMail = $this->getMail();
        $sContent = <<<HEREDOC
<?php

$sSignature

\$sMetadataVersion = '1.0';

\$aModule = array(
    'id'            => '$sModule',
    'title'         => '$sModule',
    'description'   => '',
    $sVersion
    $sAuthor
    $sUrl
    $sMail

    'extend'        => array(
        // Admin
        
        //Controllers

        // Core

        // Models
    ),

    'files'        => array(
    ),

    'templates'    => array(
    ),

    'blocks' => array(
        /*
        array(
            'template'  => 'shop_main.tpl',
            'block'     => 'admin_shop_main_leftform',
            'file'      => 'views/admin/tpl/admin_shop_main_leftform.tpl'
        ),
        */
    ),

    'settings'     => array(
    ),

    'events'       => array(
    ),
);
HEREDOC;

        return $sContent;
    }


    protected function updateMetadata($sType, $sModule, $sClass)
    {
        $sMetadataPath = getShopBasePath() . "modules/$sModule/metadata.php";
        $aModule = array();
        $sMetadataVersion = 0;
        include $sMetadataPath;
        if (!isset($aModule['extend'][$sClass])) {
            $aModule['extend'][$sClass] = "$sModule/$sType/{$sModule}__{$sClass}";
        }
        $sContent = "<?php" . PHP_EOL;
        $sContent .= $this->getSignatureContent($sModule) . PHP_EOL . PHP_EOL;
        $sContent .= $this->getMetadataContentUpdate($sMetadataVersion, $aModule);
        $this->writeFile($sMetadataPath, $sContent);
    }


    protected function getMetadataContentUpdate($sMetadataVersion, $aModule)
    {
        $sContent = "\$sMetadataVersion = '$sMetadataVersion';" . PHP_EOL . PHP_EOL;
        $sContent .= "\$aModule = array(" . PHP_EOL;
        foreach ($aModule as $sKey => $oModule) {
            if (is_array($oModule)) {
                $sContent .= PHP_EOL . "\t'$sKey'";
                $sContent .= $this->addTabs(strlen($sKey), 1);
                $sContent .= "=>\tarray(" . PHP_EOL;
                if ($sKey == 'extend') {
                    $sContent .= $this->writeExtendMetadataArray($oModule);
                }
                else {
                    $sContent .= $this->writeMetadataArray($oModule);
                }
                $sContent .= "\t)," . PHP_EOL;
            }
            else {
                $sContent .= "\t'$sKey'";
                $sContent .= $this->addTabs(strlen($sKey), 1);
                $sContent .= "=>\t'$oModule'," . PHP_EOL;
            }
        }
        $sContent .= ");";

        return $sContent;
    }


    protected function writeMetadataArray($aModule, $sContent = '')
    {
        foreach ($aModule as $sKey => $oModule) {
            if (is_array($oModule)) {
                $sContent .= "\t" . $this->writeMetadataArray($oModule, $sContent);
            }
            else {
                $dMax = strlen(array_reduce(array_keys($aModule), function ($k, $v) {
                    return (strlen($k) > strlen($v)) ? $k : $v;
                }));
                $sContent .= "\t\t'$sKey'";
                $sContent .= $this->addTabs(strlen($sKey), round(($dMax - 2) / 4));
                $sContent .= "=>\t'$oModule'," . PHP_EOL;
            }
        }

        return $sContent;
    }


    protected function writeExtendMetadataArray($aModule, $sContent = '')
    {
        $aExtendModules = $this->getSortedExtendArray($aModule);
        ksort($aExtendModules);
        $dMax = strlen(array_reduce(array_keys($aModule), function ($k, $v) {
            return (strlen($k) > strlen($v)) ? $k : $v;
        }));
        foreach ($aExtendModules as $sKey => $aModules) {
            asort($aModules);
            $sContent .= (strlen($sContent) == 0 ? '' : PHP_EOL) . "\t\t// $sKey" . PHP_EOL;
            foreach ($aModules as $sClass => $sPath) {
                $sContent .= "\t\t'$sClass'";
                $sContent .= $this->addTabs(strlen($sClass), round(($dMax - 2) / 4));
                $sContent .= "=>\t'$sPath'," . PHP_EOL;
            }
        }

        return $sContent;
    }


    protected function getSortedExtendArray($aModule)
    {
        $aResult = array();
        foreach ($aModule as $sClass => $sPath) {
            $aResult[$this->aTypes[$sClass]][$sClass] = $sPath;
        }

        return $aResult;
    }


    protected function addTabs($dLength, $dCountStart)
    {
        $dDiff = floor(($dLength - 6) / 4);
        $dCount = $dCountStart - $dDiff;

        $sContent = '';
        for ($i = 0; $i <= $dCount; $i++) {
            $sContent .= "\t";
        }

        return $sContent;
    }


    protected function getSignatureContent($sModule)
    {
        $oNow = new DateTime();
        $sNow = $oNow->format('d.m.y');
        $sSignatureDescription = $this->getSignatureDescription();
        $sLink = $this->getSignatureLink();
        $sCopyright = $this->getSignatureCopyright();
        $sAuthor = $this->getSignatureAuthor();

        return <<<HEREDOC
/**
{$sSignatureDescription}{$sLink}{$sCopyright}{$sAuthor} * @module $sModule
 * @since $sNow
 */
HEREDOC;
    }


    protected function getType(&$sClass)
    {
        if (isset($this->aTypes[$sClass])) {
            $sResult = $this->aTypes[$sClass];
        }
        else {
            $aKeys = array_keys($this->aTypes);
            $aResult = array_filter($aKeys, function ($element) use ($sClass) {
                return preg_match('/^' . $sClass . '.*/', $element);
            });
            $dCount = count($aResult);
            if ($dCount == 1) {
                $sClass = current($aResult);
                $sResult = $this->aTypes[$sClass];
            }
            else if ($dCount == 0) {
                die('Kein Typ gefunden' . PHP_EOL);
            }
            else {
                die('Der Typ ist nicht eindeutig: ' . implode($aResult, ', ') . PHP_EOL);
            }
        }

        return $sResult;
    }


    protected function loadTypes()
    {
        $this->aTypes = $this->getClasses($this->aTypes, getShopBasePath() . "application");
        $this->aTypes = $this->getClasses($this->aTypes, getShopBasePath() . "core", "core");
    }


    protected function getClasses($aTypes, $sPath, $sType = "")
    {
        $aClasses = scandir($sPath);
        foreach ($aClasses as $sClass) {
            if (substr($sClass, -4) == '.php') {
                $aTypes[substr($sClass, 0, -4)] = $sType;
            }
            else if ($sClass != '.' && $sClass != '..' && $sClass != 'views' && $sClass != 'translations' && is_dir($sPath . '/' . $sClass)) {
                $sNewType = strlen($sType) == 0 ? $sClass : $sType . "/" . $sClass;
                $aTypes = $this->getClasses($aTypes, $sPath . '/' . $sClass, $sNewType);
            }
        }

        return $aTypes;
    }


    /**
     * @param string $sVersion
     *
     * @return null|string
     */
    protected function getVersion($sVersion = '1.0.0')
    {
        return "\t'version'\t\t=>\t'$sVersion'," . PHP_EOL;
    }


    /**
     * @return null|string
     */
    protected function getAuthor()
    {
        return $this->getModuleSignaturLine('author', 'cf_generator_signature_author');
    }


    /**
     * @return null|string
     */
    protected function getUrl()
    {
        return $this->getModuleSignaturLine('url', 'cf_generator_signature_link');
    }


    /**
     * @return null|string
     */
    protected function getMail()
    {
        return $this->getModuleSignaturLine('email', 'cf_generator_signature_mail');
    }

    /**
     * @return null|string
     */
    protected function getSignatureDescription()
    {
        $sDescription = $this->getParameter('cf_generator_signature_desc');
        if ($sDescription) {
            $sDescription = ' * ' . $sDescription . PHP_EOL . ' *' . PHP_EOL;
        }
        return $sDescription;
    }


    protected function getModuleSignaturLine($sName, $sConfigName)
    {
        $sLine = $this->getParameter($sConfigName);
        if ($sLine) {
            $sLine = "\t'$sName'\t\t=>\t'$sLine',";
        }

        return $sLine;
    }


    protected function getSignatureLink()
    {
        return $this->getSignatureLine('link', 'cf_generator_signature_link');
    }


    protected function getSignatureCopyright()
    {
        return $this->getSignatureLine('copyright', 'cf_generator_signature_copyright');
    }


    protected function getSignatureAuthor()
    {
        return $this->getSignatureLine('author', 'cf_generator_signature_author');
    }

    protected function getSignatureLine($sName, $sConfigName)
    {
        $sLine = $this->getParameter($sConfigName);
        if ($sLine) {
            $sLine = " * @$sName $sLine" .PHP_EOL;
        }

        return $sLine;
    }


    public function getParameter($sParamName)
    {
        return oxRegistry::getConfig()->getConfigParam($sParamName);
    }
}
