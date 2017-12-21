<?php
class RedirectUrl {
  static private $functionalURL = "/gisclient3-maps/templates/wfm-osfc/index.html";
  static private $hmacRequestParam = "hmac";
  static private $mapsetRequestParam = "mapset";
  static private $userRequestParam  = "login";
  static private $sharedKeyFile = "../../sharedKeyFile.txt";
  private $sessionKey;


  private function readSharedKey() {
    $handle = fopen(self::$sharedKeyFile, "r");
    $contents = fread($handle, filesize(self::$sharedKeyFile ));
    fclose($handle);
    return hash("sha256", $contents);
  }

  private function getFormattedUrl($queryArr) {
    asort($queryArr);
    $result = self::$functionalURL."?";
    foreach ($queryArr as $queryParam) {
      $result .= $queryParam."&";
    }
    return substr($result, 0, strlen($result)-1);
  }
  
  private function getRedirectUrl($queryArr) {
    $result = self::$functionalURL."?";
    return $result.$queryArr[self::getIndexForParameter($queryArr, self::$mapsetRequestParam)];
  }

  private function getHmacIndex($queryParams) {
    return self::getIndexForParameter($queryParams, self::$hmacRequestParam);
  }
  
  private function getLoginIndex($queryParams) {
    return self::getIndexForParameter($queryParams, self::$userRequestParam);
  }
  
  private function getIndexForParameter($queryParams, $requestParam) {
    $requestIndex = -1;
    for ($index = 0; $index < count($queryParams); $index++) {
      if(substr($queryParams[$index], 0, strlen($requestParam."=") ) == $requestParam."=") {
        if($requestIndex == -1)
          $requestIndex = $index;
        else
          return -1;
      }
    }
    return $requestIndex;
  }

  private function isManageableRequest($queryString) {
    $params = explode("&",$queryString);
    $hmacRequestIndex = self::getHmacIndex($params);
    if($hmacRequestIndex != -1) {
      $hmacValue = str_replace(self::$hmacRequestParam."=", "", $params[$hmacRequestIndex]);
      array_splice($params, $hmacRequestIndex, 1);
      $exploitedQueryString = self::getFormattedUrl($params);
      $this->sessionKey = base64_encode(hash_hmac('sha256', $exploitedQueryString, self::readSharedKey()));
      return strcmp($this->sessionKey, $hmacValue);
    }
    return -1;
  }

  public function checkUrlRedirect($requestUri, $queryString) {
    if (substr($requestUri, 0, strlen(self::$functionalURL)) == self::$functionalURL) {
      if (self::isManageableRequest($queryString) == 0) {
         $sid = session_id();
         if(empty($sid)) {
           if(defined('GC_SESSION_NAME'))
             session_name(GC_SESSION_NAME);
           session_start();
         }
         $params = explode("&",$queryString);
         $userRequestIndex = self::getLoginIndex($params);
         $_SESSION['USERNAME'] = str_replace(self::$userRequestParam."=", "", $params[$userRequestIndex]);
         $_SESSION[self::$hmacRequestParam] = $this->sessionKey;
         header("Location: ".self::getRedirectUrl($params), true, 303);
         return 1;
      }
    }
    return $this->sessionKey;
  }
}
?>
