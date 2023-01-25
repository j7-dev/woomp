<?php
/**
 * Created by PhpStorm.
 * User: Jerry
 * Date: 2018/3/16
 * Time: 下午1:34
 */

defined('ABSPATH') || exit;

class FileTokenStorage
{
    private $fileName;

    public function __construct($filePath = null, $sandBox = false)
    {
        if($filePath == null){
            if ($sandBox) {
                $this->fileName = dirname(dirname(__FILE__))."/sandbox_pchomepay_api_token.json";
            } else {
                $this->fileName = dirname(dirname(__FILE__))."/pchomepay_api_token.json";
            }
        }else{
            $this->fileName = $filePath;
        }
    }

    /**
     * get jsonlized token from storage
     * @return string return jsonlized string
     */
    public function getTokenStr()
    {
        if(file_exists($this->fileName)) {
            return file_get_contents($this->fileName);
        }

        return false;
    }

    /**
     * save jsonlized token to storage
     * @param $token string
     * @return boolean true while success, false when fail.
     */
    public function saveTokenStr($token)
    {
        $r = file_put_contents($this->fileName, $token, FILE_TEXT);

        return $r !== false;
    }
}

