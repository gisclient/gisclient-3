<?php
include __DIR__ .'/../config/config.php';

$oldPath = ROOT_PATH . '/import/doc_old/';
$newPath = ROOT_PATH . '/import/doc/';
$oldLinkPath = ROOT_PATH . 'public/services/documents/';

$defaultParentFolder = 1;
$defaultMime = 'application/octet-stream';

// Return the list of links
function getPublicDocuments($path) {
    $result = array();
    if ($dh = opendir($path)) {
        while (($file = readdir($dh)) !== false) {
            if (is_link($path . $file)) {
                $result[] = $file;
            }
        }
        closedir($dh);
    }
    return $result;
}

set_time_limit ( 5*60 );
echo "Copying document from {$oldPath} to {$newPath}\n";
if (!file_exists($oldPath) || !is_dir($oldPath)) {
    die("\nERROR: Source directory {$oldPath} not found\n\n");
}
if (!file_exists($oldLinkPath) || !is_dir($oldLinkPath)) {
    die("\nERROR: Source link directory {$oldLinkPath} not found\n\n");
}
if (!file_exists($newPath) || !is_dir($newPath)) {
    die("\nERROR: Destination directory {$newPath} not found\n\n");
}

echo "Connecting to database " . DB_NAME . "\n";
$db = GCApp::getDB();

$oldFiles = glob("{$oldPath}*");
$oldLinkFiles = getPublicDocuments($oldLinkPath);

$sql = "INSERT INTO " . DB_SCHEMA . ".document 
        (doc_id, doc_parent_id, doc_name, doc_type, doc_public) 
        VALUES 
        (:doc_id, :doc_parent_id, :doc_name, :doc_type, :doc_public)";
$stmt = $db->prepare($sql);
foreach($oldFiles as $file) {
    $name = basename($file);
    echo "Coping {$name}\n";
    
    $isPublic = in_array($name, $oldLinkFiles);
    
    $id = $db->query("SELECT nextval('" . DB_SCHEMA . ".document_doc_id_seq')")->fetchColumn();
    $stmt->execute(array(
        'doc_id'=>$id, 
        'doc_parent_id'=>$defaultParentFolder, 
        'doc_name'=>$name, 
        'doc_type'=>$defaultMime, 
        'doc_public'=>$isPublic ? 'true':'false'));
    if (!copy($file, "{$newPath}/{$id}")) {
        die("Error coping {$file} => {$newPath}/{$id}\n");
    }
}
echo "\nDONE!";
echo "Remove old files in {$oldPath} manually\n";
echo "Remove old links in {$oldLinkPath} manually\n";
