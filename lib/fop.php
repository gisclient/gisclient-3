<?php


class EFOPError extends Exception
{
    private $output = null;
    public function __construct($message, $output='', $code = 0) {
        
        parent::__construct($message, $code);
        $this->output = $output;
    }

    final function getOutput() {   // Output of the exception
                        
        return $this->output;
    }

}


/**
 * Create the PDF file from a DOM
 * @param resource     the DOM to transform
 * @param string       the XSLT template file 
 * @param array        options. Valid parameters are:
 *                       format:   output format. Default PDF
 *                       purge:    if true remove the temporary files generated: Default true
 *                       cmd:      FOP executable name: Default GC_FOP_CMD
 *                       tmp_path: Temporary path. Defaulr GC_WEB_TMP_DIR
 *                       out_name: Output name (Default ''). If provided the file is generated with this name
 *                       prefix:   File for temporary files. Default: fop-
 *
 * @return string      name of the PDF file on an empty string when errors are encountered
 */
function runFOP(DOMDocument $dom, $xslFileName, $opt=array()) {
    $defaultOpt = array(
	    'format'=>'pdf',
	    'purge'=>true, 
        'cmd'=>defined('GC_FOP_CMD') ? GC_FOP_CMD : '',
        'tmp_path'=>defined('GC_WEB_TMP_DIR') ? GC_WEB_TMP_DIR : '/tmp/',
        'out_name'=>'',
		'prefix'=>'fop-',
    );
	
    $opt = array_merge($defaultOpt, $opt);
	
	if (empty($opt['config']))
		$opt['config'] = ROOT_PATH . '/config/fop.conf';
    
    /* Parameter check */
    if ($opt['config'] != '' && !file_exists($opt['config']))
        throw new EFOPError("Missing configuration file \"{$opt['config']}\"");
    $configParam = $opt['config'] == '' ? '' : "-c \"{$opt['config']}\"";
        
    $opt['format'] = strtolower($opt['format']);
    
    if ($dom === null) {
        throw new EFOPError('Invalid dom');
    }
    if (!in_array($opt['format'], array('pdf', 'rtf', 'txt', 'svg'))) {
        throw new EFOPError('Unsupported format "' . $opt['format'] . '"');
    }
    if ($opt['cmd'] == '') {
        throw new EFOPError('Missing parameter: cmd');
    }
    if (!is_executable($opt['cmd'])) {
        throw new EFOPError('FOP command not found: ' . $opt['cmd']);
    }
    if (!is_readable($xslFileName)) {
        throw new EFOPError('XSL not found or not readable: ' . $xslFileName);
    }
    
    if (!$opt['purge']) {
        $dom->formatOutput = true;
    }
    
    $baseName = $opt['tmp_path'] . $opt['prefix'] . md5(microtime(true) + rand(0, getrandmax()));
    $xmlFileName = $baseName . '.xml';
    $logFileName = $baseName . '.log';
    if ($opt['out_name'] == '') {
        $outFileName = $baseName . '.' . $opt['format'];
    } else {
        $outFileName = $opt['out_name'];
    }
    
    $dom->save($xmlFileName);
    $cmd = sprintf("%s $configParam -xml \"%s\" -xsl \"%s\" -%s \"%s\" 2> \"%s\"", 
                   $opt['cmd'], $xmlFileName, $xslFileName, $opt['format'], $outFileName, $logFileName);

    $stdout = array();
    exec($cmd, $stdout, $retval);
    
    if ($retval != 0) {
        if (count($stdout) > 0) {
            $stdout[] = "\n==== FOP ERROR ====\n";
        }
        //echo $logFileName;
        throw new EFOPError("FOP error #$retval\nFOP cmd: {$cmd}\n\n", implode("\n", $stdout) . file_get_contents($logFileName));
    } else {
        if ($opt['purge']) {
            @unlink($xmlFileName);
            @unlink($logFileName);
        }
        return $outFileName;
    }
}
