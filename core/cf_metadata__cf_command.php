<?php

/**
 * @author    Markus Lehmann (mar_lehmann@hotmail..com)
 * @copyright Copyright2016
 * @version   1.0
 * @since     03.09.16
 */
class cf_metadata__cf_command extends cf_metadata__cf_command_parent
{

    public function configure()
    {
        parent::configure();
        $this->addCommand('metadata:validate', new cf_metadata__cf_command_command());
    }
}

class cf_metadata__cf_command_command extends cf_command
{

    protected $oBlockProvider = null;

    public function execute()
    {
        $sModule = $this->getArgument(0);
        if (isset($sModule)) {
            $sModulesDir = substr($this->getModulesDir(), 0, -1);
            $sModuleDir = $sModulesDir . DIRECTORY_SEPARATOR . $sModule;
            $this->searchAndValidateMetadata($sModuleDir, $sModule);
        }
        else {
            $sModulesDir = substr($this->getModulesDir(), 0, -1);
            $dirHandle = opendir($sModulesDir);
            while ($sModule = readdir($dirHandle)) {
                if ($sModule != '.' && $sModule != '..') {
                    $sModuleDir = $sModulesDir . DIRECTORY_SEPARATOR . $sModule;
                    if (is_dir($sModuleDir)) {
                        $this->searchAndValidateMetadata($sModuleDir, $sModule);
                    }
                }
            }
            closedir($dirHandle);
        }
    }


    protected function searchAndValidateMetadata($sModuleDir, $sModule) {
        if ($this->_isVendorDir($sModuleDir)) {
            $dirHandle = opendir($sModuleDir);
            while ($sModule = readdir($dirHandle)) {
                if ($sModule != '.' && $sModule != '..') {
                    $sSubModuleDir = $sModuleDir . DIRECTORY_SEPARATOR . $sModule;
                    if (is_dir($sSubModuleDir)) {
                        $this->searchAndValidateMetadata($sSubModuleDir, $sModule);
                    }
                }
            }
            closedir($dirHandle);
        }
        else {
            $this->validateMetadata($sModule);
        }
    }


    protected function validateMetadata($sModule)
    {
        $sModulePath = $this->getModulePath($sModule);
        $aModule = $this->loadMetadata($sModulePath);
        if (isset($sModulePath) && isset($aModule)) {
            $blResult = true;
            $dirHandle = opendir($sModulePath);
            while ($sPath = readdir($dirHandle)) {
                if ($sPath != '.' && $sPath != '..') {
                    $sDirPath = $sModulePath . DIRECTORY_SEPARATOR . $sPath;
                    if (is_dir($sDirPath)) {
                        $blResult &= $this->validatePath($aModule, $sDirPath);
                    }
                }
            }
            closedir($dirHandle);
            if ($blResult) {
                $this->debug("Metadata of module $sModule is valid");
            }
            else {
                $this->debug("Metadata of module $sModule is NOT valid");
            }
        }
        else {
            $this->debug("Module $sModulePath not found.");
        }
    }


    protected function validatePath($aModule, $sDirPath)
    {
        $blResult = true;
        $dirHandle = opendir($sDirPath);
        while ($sPath = readdir($dirHandle)) {
            if ($sPath != '.' && $sPath != '..') {
                $sSubPath = $sDirPath . DIRECTORY_SEPARATOR . $sPath;
                if (is_dir($sSubPath)) {
                    $blResult &= $this->validatePath($aModule, $sSubPath);
                }
                else {
                    $sSubPath = $sDirPath . DIRECTORY_SEPARATOR . $sPath;
                    $blResult &= $this->validateFile($aModule, $sSubPath);
                }
            }
        }
        closedir($dirHandle);

        return $blResult;
    }


    protected function validateFile($aModule, $sFilePath)
    {
        $blResult = false;
        $sExt = $this->getExt($sFilePath);
        if ($sExt == 'php') {
            if (substr($sFilePath, -9) == '_lang.php' || basename($sFilePath)  == 'module_options.php') {
                $blResult = $this->validateLang($aModule, $sFilePath);
            }
            else {
                $blResult = $this->validateExtend($aModule, $sFilePath) || $this->validateFiles($aModule, $sFilePath);
            }

            if (!$blResult) {
                $blResult = $this->validateSmarty($aModule, $sFilePath);
            }
        }
        else if ($sExt == 'tpl') {
            $blResult = (isset($aModule['blocks']) && $this->validateBlocks($aModule, $sFilePath)) || $this->validateTemplates($aModule, $sFilePath);
        }
        else {
            if ($sExt == 'jpeg' || $sExt == 'jpg' || $sExt == 'png' || $sExt == 'gif' || $sExt == 'js' || $sExt == 'css' || $sExt == 'less' || $sExt == 'txt' || $sExt == 'sql' || $sExt == 'htaccess') {
                $blResult = true;
            }
        }

        if (!$blResult) {
            $this->debug("No entry found for: $sFilePath");
        }

        return $blResult;
    }


    protected function validateTemplates($aModule, $sFilePath)
    {
        $sFileName = basename($sFilePath);
        $sFilePath = substr($sFilePath, strlen($this->getModulesDir()));

        return isset($aModule['templates'][$sFileName]) && $aModule['templates'][$sFileName] == $sFilePath;
    }


    protected function validateBlocks($aModule, $sFilePath)
    {
        $blValid = false;
        $sModulePath = $this->getModulePath($aModule['id']);
        $sFile = substr($sFilePath, strlen($sModulePath) + 1);
        $aBlocks = $this->searchForBlock($aModule, $sFile);

        if (isset($aBlocks)) {
            $blValid = true;
            if ($aBlocks['file'] == basename($sFile)) {
                if ($sFile != "out/blocks/{$aBlocks['file']}") {
                    $this->debug("Blocks of old version must be in format: out/blocks/{$aBlocks['file']} not $sFile");
                    $blValid = false;
                }
            }
            if (!$this->getOxidDataProvider()->hasBlock($aBlocks['block'])) {
                $this->debug("Block {$aBlocks['block']} not known in standard templates");
            }
            else {
                $aTemplatePaths = $this->getOxidDataProvider()->getBlockPaths($aBlocks['block']);
                if (!isset($aTemplatePaths[$aBlocks['template']])) {
                    $this->debug("Block {$aBlocks['block']} found in template " . implode(',', $aTemplatePaths) . " but you got {$aBlocks['template']}");
                }
            }
        }

        return $blValid;
    }


    protected function searchForBlock($aModule, $sBlock)
    {
        $aResult = null;
        foreach ($aModule['blocks'] as $aBlocks) {
            if ($aBlocks['file'] == $sBlock || $aBlocks['file'] == '/' . $sBlock) {
                $aResult = $aBlocks;
            }
            else if ($aBlocks['file'] == basename($sBlock)) {
                $aResult = $aBlocks;
            }
        }

        return $aResult;
    }


    protected function validateLang($aModule, $sFilePath)
    {
        $sModulePath = $this->getModulePath($aModule['id']);
        $sRootPath = $sModulePath . DIRECTORY_SEPARATOR . 'application';
        if (!file_exists($sRootPath) || !is_dir($sRootPath)) {
            $sRootPath = $sModulePath;
        }
        $sFileName = basename($sFilePath);

        $sAdminRegex = preg_quote($sRootPath, '/') . "\/(views|out)\/admin\/..\/$sFileName";
        $sTransRegex = preg_quote($sRootPath, '/') . "\/translations\/..\/$sFileName";

        return preg_match("/" . $sAdminRegex . "/", $sFilePath) || preg_match("/" . $sTransRegex . "/", $sFilePath);
    }


    protected function validateSmarty($aModule, $sFilePath)
    {
        $blResult = false;
        $sFileName = basename($sFilePath);

        $sFunctionRegex = '/function\..*\.php/';
        $sBlockRegex = '/block\..*\.php/';
        $sCompilerRegex = '/compiler\..*\.php/';
        $sInsertRegex = '/insert\..*\.php/';
        $sModifierRegex = '/modifier\..*\.php/';
        $sFilterRegex = '/.+filter\..*\.php/';
        $sSharedRegex = '/shared\..*\.php/';

        if (preg_match($sFunctionRegex, $sFileName) || preg_match($sBlockRegex, $sFileName) || preg_match($sCompilerRegex, $sFileName) || preg_match($sInsertRegex, $sFileName) || preg_match($sModifierRegex, $sFileName) || preg_match($sFilterRegex, $sFileName) || preg_match($sSharedRegex, $sFileName)) {
            $this->debug("We expect file $sFileName is a smarty plugin.");
            $sModulePath = $this->getModulePath($aModule['id']);
            $sFilePath = substr($sFilePath, strlen($sModulePath), -1 * strlen($sFileName));
            if ($sFilePath != '/core/smarty/plugins/') {
                $this->debug("We recommend the path $sModulePath/core/smarty/plugins for smarty plugins.");
            }
            $blResult = true;
        }

        return $blResult;
    }


    protected function validateExtend($aModule, $sFilePath)
    {
        $blResult = $this->validateExtendByParentClass($aModule, $sFilePath);
        if (!$blResult) {
            $blResult = $this->validateExtendByClass($aModule, $sFilePath);
        }

        return $blResult;
    }


    protected function validateExtendByParentClass($aModule, $sFilePath) {
        $sParentClass = $this->getParentClass($sFilePath);
        $sFilePath = substr($sFilePath, strlen($this->getModulesDir()), -4);

        return isset($sParentClass) && isset($aModule['extend'][$sParentClass]) && $aModule['extend'][$sParentClass] == $sFilePath;
    }


    protected function validateExtendByClass($aModule, $sFilePath) {
        $blReturn = false;
        $sTypePath = substr($sFilePath, strlen($this->getModulesDir()), -4);
        foreach ($aModule['extend'] as $sParentClass => $sPath) {
            if ($sTypePath == $sPath) {
                $blReturn = true;
                $sFileName = substr(basename($sFilePath), 0, -4);
                $sFilePath = current(explode($sParentClass, $sFileName));
                $this->debug("A extended class of oxid should be in the format {$sFilePath}__{$sParentClass}");
                break;
            }
        }


        return $blReturn;
    }


    protected function validateFiles($aModule, $sFilePath)
    {
        $sFileName = substr(basename($sFilePath), 0, -4);
        $sFilePath = substr($sFilePath, strlen($this->getModulesDir()));

        $aFiles = array_change_key_case($aModule['files']);

        return isset($aFiles[$sFileName]) && $aFiles[$sFileName] == $sFilePath;
    }


    protected function loadMetadata($sModulePath)
    {
        $aModule = array();
        $sMetadataPath = $sModulePath . DIRECTORY_SEPARATOR . "metadata.php";
        if (file_exists($sMetadataPath)) {
            include $sMetadataPath;
        }
        else {
            $this->debug("Skip Module due to no metadata found: $sMetadataPath.");
        }

        return $aModule;
    }


    protected function getParentClass($sFilePath)
    {
        $sParent = null;
        $iPos = strrpos($sFilePath, '__');
        if ($iPos !== false) {
            $sParent = substr($sFilePath, $iPos + 2, -4);
        }

        return $sParent;
    }


    /**
     * @return cf_oxid_data_provider
     */
    protected function getOxidDataProvider()
    {
        if (!isset($this->oBlockProvider)) {
            $this->oBlockProvider = oxRegistry::get('cf_oxid_data_provider');
        }

        return $this->oBlockProvider;
    }

    protected function _isVendorDir($sModuleDir)
    {
        return is_dir($sModuleDir) && file_exists($sModuleDir . '/vendormetadata.php');
    }
}
