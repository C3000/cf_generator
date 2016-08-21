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

\$aArgs = \$argv;
// remove cmd name
array_shift(\$aArgs);
if (count(\$aArgs) > 0) {
    \$sModule = strtolower(\$aArgs[0]);
    \$sClass = isset(\$aArgs[1]) ? strtolower(\$aArgs[1]) : null;
    new cf_generator(\$sModule, \$sClass);
}
else {
    die('Zu wenig Argumente' . PHP_EOL);
}
HEREDOC;
    }

    protected function getSignatureContent($sModule)
    {
        $oNow = new DateTime();
        $sNow = $oNow->format('d.m.y');
        $sYear = $oNow->format('Y');

        return <<<HEREDOC
/**
 * @copyright Copyright$sYear, Markus Lehmann 
 * @author Markus Lehmann <mar_lehmann@hotmail.com>
 * @module $sModule
 * @since $sNow
 */
HEREDOC;
    }

}
