<?php

/**
 * @author    Markus Lehmann (mar_lehmann@hotmail.com)
 * @copyright Copyright2016, Markus Lehmann
 * @version   1.0
 * @since     21.08.16
 */
class cf_generator__cf_command extends cf_generator__cf_command_parent
{

    public function configure()
    {
        parent::configure();
        $this->addCommand('generator', new cf_generator__cf_command_command());
    }
}

class cf_generator__cf_command_command extends cf_command
{

    public function execute()
    {
        $sModule = $this->getArgument(0);
        $sClass = $this->getArgument(1);

        if (!isset($sClass)) {
            $this->generateModule($sModule);
        }
        else if (substr($sClass, -4) === '.tpl') {
            $this->generateTpl($sModule, $sClass);
        }
        else {
            $oOxidDataProvider = oxRegistry::get('cf_oxid_data_provider');
            $oOxidDataProvider->loadTypes();
            $sType = $oOxidDataProvider->getType($sClass);
            if (isset($sType)) {
                $this->generateType($sType, $sModule, $sClass);
            }
            else {
                $oOxidDataProvider->loadBlocks();
                $sBlockPath = $oOxidDataProvider->getBlockPath($sClass);
                if (isset($sBlockPath)) {
                    $this->generateBlock($sBlockPath, $sModule, $sClass);
                }
                else {
                    $this->debug("No matching file found.");
                }
            }
        }
    }


    protected function generateModule($sModule)
    {
        $blResult = false;
        $sModulePath = $this->getModulePath($sModule);
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
        $this->checkModule($sModule);

        $sTypePath = $this->getModulePath($sModule) . "/$sType";

        if (!file_exists($sTypePath)) {
            mkdir($sTypePath, 0777, true);
        }

        if (file_exists($sTypePath) && is_dir($sTypePath) && is_writable($sTypePath)) {
            $sFilePath = $sTypePath . "/{$sModule}__{$sClass}.php";
            if (!file_exists($sFilePath)) {
                $sContent = $this->getClassContent($sModule, $sClass);
                $this->writeFile($sFilePath, $sContent);
                $this->updateMetadataAddType($sType, $sModule, $sClass);
            }
            else {
                die("Die Datei existiert bereits: $sFilePath" . PHP_EOL);
            }
        }
        else {
            die("Typpfad existiert nicht oder ist nicht schreibbar" . PHP_EOL);
        }
    }


    protected function generateBlock($sBlockPath, $sModule, $sBlock)
    {
        $this->checkModule($sModule);

        $sTypePath = $this->getModulePath($sModule) . "/views/blocks";

        if (!file_exists($sTypePath)) {
            mkdir($sTypePath, 0777, true);
        }

        if (file_exists($sTypePath) && is_dir($sTypePath) && is_writable($sTypePath)) {
            $sFilePath = $sTypePath . "/$sBlock.tpl";
            if (!file_exists($sFilePath)) {
                $sContent = $this->getBlockContent();
                $this->writeFile($sFilePath, $sContent);
                $this->updateMetadataAddBlock($sBlockPath, $sModule, $sBlock);
            }
            else {
                die("Die Datei existiert bereits: $sFilePath" . PHP_EOL);
            }
        }
        else {
            die("Typpfad existiert nicht oder ist nicht schreibbar" . PHP_EOL);
        }
    }


    protected function generateTpl($sModule, $sClass)
    {
        $this->checkModule($sModule);

        $sTypePath = $this->getModulePath($sModule) . "/$sClass";

        if (!file_exists($sTypePath)) {
            mkdir($sTypePath, 0777, true);
        }
    }


    protected function checkModule($sModule)
    {
        $sModulePath = $this->getModulePath($sModule);

        $this->generateModule($sModule);

        if (!(file_exists($sModulePath) && is_dir($sModulePath) && is_writable($sModulePath))) {
            die("Modulpfad existiert nicht oder ist nicht schreibbar: $sModulePath" . PHP_EOL);
        }
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


    protected function updateMetadataAddType($sType, $sModule, $sClass)
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


    protected function updateMetadataAddBlock($sBlockPath, $sModule, $sBlock)
    {
        $sMetadataPath = getShopBasePath() . "modules/$sModule/metadata.php";
        $aModule = array();
        $sMetadataVersion = 0;
        include $sMetadataPath;
        $aModule['blocks'][] = array(
            'template' => $sBlockPath,
            'block' => $sBlock,
            'file' => "views/blocks/$sBlock.tpl"
        );
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
                    $sContent .= $this->writeMetadataArray($oModule, 2);
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


    protected function writeMetadataArray($aModule, $dLevel = 1)
    {
        $sContent = '';
        foreach ($aModule as $sKey => $oModule) {
            $sTabs = str_repeat("\t", $dLevel);
            if (is_array($oModule)) {
                $sContent .= "{$sTabs}array(" . PHP_EOL;
                $sContent .= $this->writeMetadataArray($oModule, $dLevel + 1);
                $sContent .= "{$sTabs})," . PHP_EOL;
            }
            else {
                $dMax = strlen(array_reduce(array_keys($aModule), function ($k, $v) {
                    return (strlen($k) > strlen($v)) ? $k : $v;
                }));
                $sContent .= "{$sTabs}\t'$sKey'";
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
        $oOxidDataGenerator = oxRegistry::get('cf_oxid_data_provider');
        $aResult = array();
        foreach ($aModule as $sClass => $sPath) {
            $sType = $oOxidDataGenerator->getType($sClass);
            $aResult[$sType][$sClass] = $sPath;
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
            $sLine = " * @$sName $sLine" . PHP_EOL;
        }

        return $sLine;
    }


    protected function getParameter($sParamName)
    {
        return oxRegistry::getConfig()->getConfigParam($sParamName);
    }


    /**
     * @param $sModule
     *
     * @return string
     */
    protected function getModulePath($sModule)
    {
        $sModulePath = getShopBasePath() . "modules/$sModule";

        return $sModulePath;
    }


    /**
     * @return string
     */
    protected function getBlockContent()
    {
        return <<<HEREDOC
[{\$smarty.block.parent}]
HEREDOC;
    }
}