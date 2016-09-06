<?php

/**
 * @author    Markus Lehmann (mar_lehmann@hotmail..com)
 * @copyright Copyright2016
 * @version   1.0
 * @since     03.09.16
 */
class cf_command
{

    /**
     * @var array
     */
    protected $aArgs;

    /**
     * @var array
     */
    protected $aCommands;


    /**
     * cf_command constructor.
     */
    public function __construct()
    {
        $aArgs = $_SERVER['argv'];
        array_shift($aArgs);
        $this->aArgs = $aArgs;
        $this->configure();
    }


    public function getCommand()
    {
        return $this->aArgs[0];
    }


    public function addCommand($sCommand, $sClass)
    {
        $this->aCommands[$sCommand] = $sClass;
    }


    public function getArgument($iIndex)
    {
        return $this->aArgs[$iIndex + 1];
    }


    protected function getModulePath($sModule)
    {
        $oModule = oxNew("oxmodule");
        $sModulePath = $oModule->getModulePath($sModule);
        if (isset($sModulePath)) {
            $sModulePath = $this->getModulesDir() . $sModulePath;
            if (!file_exists($sModulePath) || !is_dir($sModulePath)) {
                $sModulePath = null;
            }
        }

        return $sModulePath;
    }


    /**
     * @return mixed
     */
    protected function getModulesDir()
    {
        return $this->getConfig()->getModulesDir();
    }


    protected function debug($sMessage, $iType = 0)
    {
        print $sMessage . PHP_EOL;
    }


    protected function getConfig()
    {
        return oxRegistry::getConfig();
    }


    protected function getExt($sFile)
    {
        return pathinfo($sFile, PATHINFO_EXTENSION);
    }


    public function run()
    {
        if (isset($this->aCommands[$this->getCommand()])) {
            $oCommand = $this->aCommands[$this->getCommand()];
            $oCommand->execute();
        }
        else {
            $this->debug("Command {$this->getCommand()} not known");
        }
    }


    public function configure()
    {
    }
}
