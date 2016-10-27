<?php

/**
 * @author    Markus Lehmann (mar_lehmann@hotmail.com)
 * @copyright Copyright2016, Markus Lehmann
 * @version   1.0
 * @since     21.08.16
 */

class cf_generator_events
{

    public static function onActivate()
    {
        self::createBinFile();
	    return true;
    }


	public static function onDeActivate() {
        $sFilePath = self::getGeneratorFilePath();
		return !file_exists($sFilePath) || unlink($sFilePath);
	}

	protected static function createBinFile() {
        $sBinPath = self::getGeneratorFilePath();
        $sFileContent = self::getGeneratorFileContent();
        self::writeFile($sBinPath, $sFileContent);
    }


    /**
     * @return string
     */
    protected static function getGeneratorFilePath()
    {
        $sBinDir = realpath(oxRegistry::getConfig()->getShopConfVar('sShopDir') . '/bin');
        return $sBinDir . '/generator.php';
    }

    protected function writeFile($sFilePath, $sContent)
    {
        $oFile = fopen($sFilePath, "w");
        fwrite($oFile, $sContent);
        fclose($oFile);
    }

    protected function getGeneratorFileContent() {
        $sSignature = self::getSignatureContent('cf_generator');
        return <<<HEREDOC
<?php

$sSignature

require_once dirname(__FILE__) . "/../bootstrap.php";

\$oCommand = oxNew('cf_command');
\$oCommand->run();
HEREDOC;
    }

    protected function getSignatureContent($sModule)
    {
        $oNow = new DateTime();
        $sYear = $oNow->format('Y');

        return <<<HEREDOC
/**
 * @copyright Copyright$sYear, Markus Lehmann 
 * @author Markus Lehmann <mar_lehmann@hotmail.com>
 * @module $sModule
 * @since 28.10.2016
 */
HEREDOC;
    }

}
