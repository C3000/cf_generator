<?php

/**
 * @author    Markus Lehmann (mar_lehmann@hotmail.com)
 * @copyright Copyright2016, Markus Lehmann
 * @version   1.0
 * @since     21.08.16
 */
class cf_generator__cf_command extends cf_generator__cf_command_parent
{

    /**
     *
     */
    public function configure()
    {
        parent::configure();
        $this->addCommand('generator', new cf_generator__cf_command_command());
    }
}

/**
 * Class cf_generator__cf_command_command
 */
class cf_generator__cf_command_command extends cf_command
{

    /**
     * @var string|null
     */
    protected $sModuelPath = null;

    /**
     * @var string|null
     */
    protected $sModuleApplicationPath = null;

    /**
     * @var bool|null
     */
    protected $blIsApplicationModulePathAvailable = null;

    protected $sTab = '    ';


    /**
     *
     */
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
        else if (substr($sClass, -4) === '.php') {
            $this->generateCustomFile($sModule, $sClass);
        }
        else {
            $oOxidDataProvider = oxRegistry::get('cf_oxid_data_provider');
            $sType = $oOxidDataProvider->getType($sClass);
            if (isset($sType)) {
                $this->generateType($sType, $sModule, $sClass);
            }
            else {
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


    /**
     * @param string $sModule
     *
     * @return bool
     */
    protected function generateModule($sModule)
    {
        $blResult = false;
        $sModulePath = $this->getModulePath($sModule);
        if (!file_exists($sModulePath)) {
            $blResult = mkdir($sModulePath);
            $sMetadataPath = $this->getMetadataPath($sModulePath);
            $sMetadataContent = $this->getMetaDataContent($sModule);
            $blResult = $blResult && $this->writeFile($sMetadataPath, $sMetadataContent);
        }

        return $blResult;
    }


    /**
     * @param string $sType
     * @param string $sModule
     * @param string $sClass
     */
    protected function generateType($sType, $sModule, $sClass)
    {
        $sFilePath = $this->getFilePath($sModule, $sType, "{$sModule}__{$sClass}.php");
        $sModulePath = $this->getModulePath($sModule);
        $sContent = $this->getClassContent($sModule, $sClass);
        $this->writeFile($sFilePath, $sContent);
        $sFilePath = $sModule . substr(str_replace($sModulePath, '', $sFilePath), 0, -4);
        $this->updateMetadataAddFile($sModule, 'extend', $sClass, $sFilePath);
    }


    /**
     * @param string $sBlockPath
     * @param string $sModule
     * @param string $sBlock
     */
    protected function generateBlock($sBlockPath, $sModule, $sBlock)
    {
        $sFilePath = $this->getFilePath($sModule, "views/blocks", "$sBlock.tpl");
        $sContent = $this->getBlockContent();
        $this->writeFile($sFilePath, $sContent);
        $this->updateMetadataAddBlock($sBlockPath, $sModule, $sBlock);
    }


    /**
     * @param string $sModule
     * @param string $sTemplatePath
     */
    protected function generateTpl($sModule, $sTemplatePath)
    {
        $sTemplate = basename($sTemplatePath);
        $sType = "views/";
        if (strpos($sTemplatePath, 'admin/') !== 0 && strpos($sTemplatePath, '/admin/') === false) {
            $sType .= "tpl/";
        }
        $sType .= substr($sTemplatePath, 0, -1 * (strlen($sTemplate) + 1));
        $sFilePath = $this->getFilePath($sModule, $sType, $sTemplate);
        $sContent = $this->getTemplateContent();
        $this->writeFile($sFilePath, $sContent);
        $sFilePath = substr($sFilePath, strlen($this->getModulePath($sModule)) - strlen($sModule));
        $this->updateMetadataAddFile($sModule, 'templates', basename($sFilePath), $sFilePath);
    }


    /**
     * @param string $sModule
     * @param string $sClassPath
     */
    protected function generateCustomFile($sModule, $sClassPath)
    {
        $sClass = basename($sClassPath);
        $sClassPath = substr($sClassPath, 0, -1 * (strlen($sClass) + 1));
        $sFilePath = $this->getFilePath($sModule, $sClassPath, "{$sModule}_{$sClass}");
        $sContent = $this->getFileContent($sModule, basename($sClass, '.php'));
        $this->writeFile($sFilePath, $sContent);
        $sFilePath = substr($sFilePath, strlen($this->getModulePath($sModule)) - strlen($sModule));
        $this->updateMetadataAddFile($sModule, 'files', basename($sFilePath, '.php'), $sFilePath);
    }


    /**
     * @param string $sTypeDir
     *
     * @return bool
     */
    protected function validFileDir($sTypeDir)
    {
        return (file_exists($sTypeDir) && is_dir($sTypeDir) && is_writable($sTypeDir)) || die("Typpfad existiert nicht oder ist nicht schreibbar" . PHP_EOL);
    }


    /**
     * @param string $sFilePath
     *
     * @return bool
     */
    protected function validateFile($sFilePath)
    {
        return !file_exists($sFilePath) || die("Die Datei existiert bereits: $sFilePath" . PHP_EOL);
    }


    /**
     * @param string $sModule
     */
    protected function checkModule($sModule)
    {
        $sModulePath = $this->getModulePath($sModule);
        $this->generateModule($sModule);
        if (!(file_exists($sModulePath) && is_dir($sModulePath) && is_writable($sModulePath))) {
            die("Modulpfad existiert nicht oder ist nicht schreibbar: $sModulePath" . PHP_EOL);
        }
    }


    /**
     * @param string $sFilePath
     * @param string $sContent
     *
     * @return bool
     */
    protected function writeFile($sFilePath, $sContent)
    {
        $blResult = false;
        $oFile = fopen($sFilePath, "w");
        if ($oFile !== false) {
            fwrite($oFile, $sContent);
            $blResult = fclose($oFile);
        }

        return $blResult;
    }


    /**
     * @param string $sBlockPath
     * @param string $sModule
     * @param string $sBlock
     */
    protected function updateMetadataAddBlock($sBlockPath, $sModule, $sBlock)
    {
        $sFile = "views/blocks/$sBlock.tpl";
        if ($this->isApplicationModulePathAvailable($this->getModulePath($sModule))) {
            $sFile = 'application/' . $sFile;
        }
        $aContent = array('template' => $sBlockPath,
                          'block' => $sBlock,
                          'file' => $sFile);
        $this->updateMetadataAddFile($sModule, 'blocks', null, $aContent);
    }


    /**
     * @param string       $sModule
     * @param string       $sModuleKey
     * @param string       $sBaseFile
     * @param array|string $oContent
     */
    protected function updateMetadataAddFile($sModule, $sModuleKey, $sBaseFile, $oContent)
    {
        $sModulePath = $this->getModulePath($sModule);
        $sMetadataPath = $this->getMetadataPath($sModulePath);
        $aModule = $this->loadMetadataModule($sMetadataPath);
        if (isset($sBaseFile)) {
            if (!isset($aModule[$sModuleKey][$sBaseFile])) {
                $aModule[$sModuleKey][$sBaseFile] = $oContent;
            }
        }
        else {
            $aModule[$sModuleKey][] = $oContent;
        }
        $this->writeMetadataFile($sModule, '1.0', $aModule, $sMetadataPath);
    }


    /**
     * @param string $sModule
     * @param string $sMetadataVersion
     * @param array  $aModule
     * @param string $sMetadataPath
     */
    protected function writeMetadataFile($sModule, $sMetadataVersion, $aModule, $sMetadataPath)
    {
        $sContent = "<?php" . PHP_EOL;
        $sContent .= $this->getSignatureContent($sModule) . PHP_EOL . PHP_EOL;
        $sContent .= $this->getMetadataContentUpdate($sMetadataVersion, $aModule);
        $this->writeFile($sMetadataPath, $sContent);
    }


    /**
     * @param string $sMetadataVersion
     * @param array  $aModule
     *
     * @return string
     */
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
                else if ($sKey == 'blocks') {
                    $sContent .= $this->writeMetadataArray($oModule, 2);
                }
                else {
                    $sContent .= $this->writeMetadataArray($oModule, 1);
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


    /**
     * @param array $aModule
     * @param int   $dLevel
     *
     * @return string
     */
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


    /**
     * @param array  $aModule
     * @param string $sContent
     *
     * @return string
     */
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


    /**
     * @param array $aModule
     *
     * @return array
     */
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


    /**
     * @param double $dLength
     * @param double $dCountStart
     *
     * @return string
     */
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


    /**
     * @param string $sModule
     *
     * @return string
     */
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
        return "{$this->sTab}'version'{$this->sTab}{$this->sTab}=>{$this->sTab}'$sVersion'," . PHP_EOL;
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
     * @return mixed|string
     */
    protected function getSignatureLink()
    {
        return $this->getSignatureLine('link', 'cf_generator_signature_link');
    }


    /**
     * @return mixed|string
     */
    protected function getSignatureCopyright()
    {
        return $this->getSignatureLine('copyright', 'cf_generator_signature_copyright');
    }


    /**
     * @return mixed|string
     */
    protected function getSignatureAuthor()
    {
        return $this->getSignatureLine('author', 'cf_generator_signature_author');
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


    /**
     * @param string $sName
     * @param string $sConfigName
     *
     * @return mixed|string
     */
    protected function getModuleSignaturLine($sName, $sConfigName)
    {
        $sLine = $this->getParameter($sConfigName);
        if ($sLine) {
            $sLine = "{$this->sTab}'$sName'{$this->sTab}{$this->sTab}=>{$this->sTab}'$sLine',";
        }

        return $sLine;
    }


    /**
     * @param string $sName
     * @param string $sConfigName
     *
     * @return mixed|string
     */
    protected function getSignatureLine($sName, $sConfigName)
    {
        $sLine = $this->getParameter($sConfigName);
        if ($sLine) {
            $sLine = " * @$sName $sLine" . PHP_EOL;
        }

        return $sLine;
    }


    /**
     * @param string $sParamName
     *
     * @return mixed
     */
    protected function getParameter($sParamName)
    {
        return oxRegistry::getConfig()->getConfigParam($sParamName);
    }


    /**
     * @param string $sModule
     *
     * @return string
     */
    protected function getModulePath($sModule)
    {
        if (!isset($this->sModuelPath)) {
            $this->sModuelPath = getShopBasePath() . "modules/$sModule";
        }

        return $this->sModuelPath;
    }


    /**
     * @param string $sMetadataPath
     *
     * @return array
     */
    protected function loadMetadataModule($sMetadataPath)
    {
        $aModule = array();
        include $sMetadataPath;

        return $aModule;
    }


    /**
     * @param string $sModulePath
     *
     * @return string
     */
    protected function getMetadataPath($sModulePath)
    {
        return $sModulePath . "/metadata.php";
    }


    /**
     * @param string $sTypePath
     */
    protected function createDirs($sTypePath)
    {
        if (!file_exists($sTypePath)) {
            mkdir($sTypePath, 0777, true);
        }
    }


    /**
     * @param string $sModulePath
     *
     * @return string
     */
    protected function getModuleApplicationPath($sModulePath)
    {
        if (!isset($this->sModuleApplicationPath)) {
            if ($this->isApplicationModulePathAvailable($sModulePath)) {
                $this->sModuleApplicationPath = $sModulePath . '/application';
            }
            else {
                $this->sModuleApplicationPath = $sModulePath;
            }
        }

        return $this->sModuleApplicationPath;
    }


    /**
     * @param string $sModulePath
     *
     * @return bool
     */
    protected function isApplicationModulePathAvailable($sModulePath)
    {
        if (!isset($this->blIsApplicationModulePathAvailable)) {
            $this->blIsApplicationModulePathAvailable = file_exists($sModulePath . '/application');
        }

        return $this->blIsApplicationModulePathAvailable;
    }


    /**
     * @param string $sModule
     * @param string $sType
     *
     * @return string
     */
    protected function getTypeDir($sModule, $sType)
    {
        $this->checkModule($sModule);

        $sModulePath = $this->getModulePath($sModule);
        $sApplicationModulePath = $this->getModuleApplicationPath($sModulePath);
        $sTypeDir = $sApplicationModulePath . $sType;

        $this->createDirs($sTypeDir);

        return $this->validFileDir($sTypeDir) ? $sTypeDir : null;
    }


    /**
     * @param $sModule
     * @param $sType
     * @param $sFile
     *
     * @return string
     */
    protected function getFilePath($sModule, $sType, $sFile)
    {
        $sTypeDir = $this->getTypeDir($sModule, DIRECTORY_SEPARATOR . $sType);

        $sFilePath = $sTypeDir . DIRECTORY_SEPARATOR . $sFile;

        return $this->validateFile($sFilePath) ? $sFilePath : null;
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


    /**
     * @return string
     */
    protected function getTemplateContent()
    {
        return "";
    }


    /**
     * @param string $sModule
     * @param string $sClass
     *
     * @return string
     */
    protected function getFileContent($sModule, $sClass)
    {
        $sSignature = $this->getSignatureContent($sModule);

        return <<<HEREDOC
<?php

$sSignature
 
class {$sModule}_{$sClass} {

}
HEREDOC;
    }


    /**
     * @param string $sModule
     * @param string $sClass
     *
     * @return string
     */
    protected function getClassContent($sModule, $sClass)
    {
        $sSignature = $this->getSignatureContent($sModule);

        return <<<HEREDOC
<?php

$sSignature
 
class {$sModule}__{$sClass} extends {$sModule}__{$sClass}_parent {

}
HEREDOC;
    }


    /**
     * @param string $sModule
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

        return <<<HEREDOC
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
    ),

    'files'        => array(
    ),

    'templates'    => array(
    ),

    'blocks' => array(
    ),

    'settings'     => array(
    ),

    'events'       => array(
    ),
);
HEREDOC;
    }
}