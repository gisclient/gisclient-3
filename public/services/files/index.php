<?php
include_once "../../../config/config.php";
require_once ROOT_PATH . 'lib/GCService.php';

$gcService = GCService::instance();
$gcService->startSession();

if(!defined('UPLOADED_FILES_PRIVATE_PATH')) die('undefined upload path');

$self = $_SERVER['PHP_SELF'];
$pos = strrpos($self, '/') + 1;
$selfDir = substr($self, 0, $pos);
$request = $_SERVER['REQUEST_URI'];
$path = str_replace($selfDir, '', $request);

$parts = explode('/', $path);
if(count($parts) > 1) die('invalid file url');

$fileName = $parts[0];

if(!file_exists(UPLOADED_FILES_PRIVATE_PATH . $fileName)) die('404');

try {
    deliverFile(UPLOADED_FILES_PRIVATE_PATH . $fileName);
} catch(Exception $e) {
    die($e->getMessage());
}

die();



/**
 * Return the mime type of a file
 * @param string       the file extension
 * @param array        options. Valid parameters are:
 *                       format:   output format. Default calculated from file extension
 *                       purge:    if true remove the file after delivery. Default false
 *                       mime:     Document mime. If specified this mime is returned
 *                       header:   Extra header
 *
 * @return boolea      return true on success
 */
function getMimeFromFileExt($ext) {

    $mimes = array(
        'application/pdf' => 'pdf',
        'application/zip' => 'zip',
        'application/msword' => array('doc', 'dot'),
        'application/vnd.ms-excel' => array('xla', 'xlc', 'xlm', 'xls', 'xlt', 'xlw'),
        'application/vnd.ms-powerpoint' => array('pot', 'pps', 'ppt'),
        'application/postscript' => array('ai', 'eps', 'ps'),
        'application/rtf' => 'rtf',
        'application/vnd.google-earth.kml+xml' => 'kml',
        'application/vnd.google-earth.kmz' => 'kmz',
        'application/gpx+xml' => 'gpx',
        'application/x-compress' => 'z',
        'application/x-compressed' => 'tgz',
        'application/x-gtar' => 'gtar',
        'application/x-gzip' => 'gz',
        'application/x-tar' => 'tar',
        'application/x-javascript' => 'js',
        'application/x-shockwave-flash' => 'swf',
        'audio/mid' => array('mid', 'rmi'),
        'audio/x-wav' => 'wav',
        'audio/mpeg' => 'mp3',
        'image/gif' => 'gif',
        'image/bmp' => 'bmp',
        'image/pipeg' => 'jfif',
        'image/svg+xml' => 'svg',
        'image/tiff' => array('tif', 'tiff'),
        'image/png' => array('png', 'png16', 'png24', 'png32'),
        'image/jpeg' => array('jpe', 'jpeg', 'jpg'),
        'text/plain' => array('txt', 'c', 'h', 'php', 'php3', 'php4'),
        'text/html' => array('htm', 'html', 'stm'),
        'text/css' => 'css',
        'video/mpeg' => array('mp2', 'mpa', 'mpe', 'mpeg', 'mpg', 'mpv2'),
        'video/quicktime' => array('qt', 'mov'),
        'video/x-msvideo' => 'avi',
        // MS Office 2007
        'application/vnd.openxmlformats' => array('docx', 'pptx', 'xlsx')
    );

    if (isset($ext[0]) && $ext[0] == '.') {
        $ext = substr($ext, 1);
    }
    $ext = strtolower($ext);

    foreach ($mimes as $key => $val) {
        if (is_array($val) && in_array($ext, $val)) {
            return $key;
        } else {

            if ($ext == $val) {
                return $key;
            }
        }
    }
    return 'application/octet-stream';
}

/**
 * Deliver the specified file
 * @param string       the file to delivery
 * @param array        options. Valid parameters are:
 *                       format:       output format. Default calculated from file extension
 *                       purge:        if true remove the file after delivery. Default false
 *                       mime:         Document mime. If specified this mime is returned
 *                       disposition:  Content disposition: inline or attachment.
 *                       name:         file name (for header). Default basename($fileName)
 *                       header:       Extra header
 *                       die:          If true die after send
 *                       cacheable:    If true don't send the cache header
 *                       cache_ttl:    Time to live of a cacheable object (cacheable must be true)
 *
 * @return boolea      return true on success
 */
function deliverFile($fileName, $opt=array()) {

    $defaultOpt = array(
        'format' => '',
        'name' => '',
        'purge' => false,
        'mime' => '',
        'disposition' => 'inline',
        'header' => array(),
        'die' => true,
        'cacheable' => false,
        'cache_ttl' => 30 * 60 * 60
    );

    $opt = array_merge($defaultOpt, $opt);

    if (!is_readable($fileName)) {
        throw new Exception('File not found or not readable: ' . $fileName);
    }

    /* MIME TYPE */
    $mime = $opt['mime'];
    if ($mime == '') {
        if ($opt['format'] == '') {
            $opt['format'] = substr(strrchr($fileName, '.'), 1);
        }
        $mime = getMimeFromFileExt($opt['format']);
    }

    if ($opt['name'] == '') {
        $opt['name'] = basename($fileName);
    }

    // Problem with some version of IE
    $ieProblemExt = array('zip');  // File
    if (in_array($opt['format'], $ieProblemExt)) {
        // Don't do double compression on zip files!
        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }
        @apache_setenv('no-gzip', 1);
    }

    /* ETAG */
    if (isset($opt['header']['etag'])) {
        if ($opt['header']['etag'] != '') {
            header("ETag: \"$hash\"");
        }
    } else {
        $hash = sprintf('%8X', time()) . md5(microtime(true) + rand(0, getrandmax()));
        header("ETag: \"$hash\"");
    }

    header("Content-Type: " . $mime . "; charset=UTF-8");
    header("Content-Length: " . filesize($fileName));

    // Mode for Include File
    if ($opt['disposition'] == 'attachment' || $opt['disposition'] == 'download')
        header("Content-Disposition: attachment; filename=\"" . $opt['name'] . "\"");
    else if ($opt['disposition'] == 'inline')
        header("Content-Disposition: inline; filename=\"" . $opt['name'] . "\"");

    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($fileName)) . ' GMT');
    if ($opt['cacheable'] === true) {
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $opt['cache_ttl']) . ' GMT');
        header('Cache-Control: max-age=' . $opt['cache_ttl']);
        header("Pragma: public", true);
    } else {
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");
    }

    /* Extra header */
    if (is_array($opt['header'])) {
        foreach ($opt['header'] as $key => $val) {
            if (is_numeric($key)) {
                header($val);
            } else {
                header("$key: $val");
            }
        }
    } else {
        header($opt['header']);
    }

	//SS: Removed because of error flush();
	
    // read file faild on big files
    //readfile($fileName);

    if (($handle = fopen($fileName, 'rb')) === FALSE) {
        throw new Exception("Could not open $fileName");
    }
    $buffer = '';
    while (!feof($handle)) {
        echo fread($handle, 8 * 1024);
        //SS: Removed because of error flush();
    }
    fclose($handle);

    if ($opt['purge']) {
        @unlink($fileName);
    }
    if ($opt['die']) {
        die();
    }
}