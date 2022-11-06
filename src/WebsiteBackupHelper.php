<?php

namespace Backdoor\WebsiteBackup;

class WebsiteBackupHelper{

    public static function getDomain($url){
        $base_url = parse_url($url, PHP_URL_HOST);
        if(!$base_url){
            $base_url = $url;
        }
        return 'https://'.$base_url. '/';
    }

    public static function findImageURl($image, $base_url){

        $attribute = array('src', 'href', 'data-src', 'data-href', 'srcset');

        foreach($attribute as $attr){
            if($image->hasAttribute($attr)){
                $url = $image->getAttribute($attr);
                if($url && strpos($url, 'data:') === false){
                    if(strpos($url, 'http') === false){
                        // check url has / in first index
                        if(strpos($url, '/') === 0){
                            $url = substr($url, 1);
                        }
                        $url = $base_url.$url;
                    }
                    return $url;
                }
            }
        }
        return false;
    }

    public static function replaceImageURL($image, $path){
        // replace all images src with local path
        $attribute = array('src', 'href', 'data-src', 'data-href', 'srcset');

        foreach($attribute as $attr){
            if($image->hasAttribute($attr)){
                $image->removeAttribute($attr);
                $image->setAttribute($attr, $path);
            }
        }
        return $image;
    }

    public static function replaceFileName($filename, $path){
        $file_name = $filename;

        $list = array(' ', '(', ')', '[', ']', '{', '}', '?', '/', ':', ';', '!', '@', '#', '$', '%', '^', '&', '*', '+', '=', '|', '\\', '"', "'", '<', '>', ',','`', '~');

        foreach($list as $item){
            $file_name = str_replace($item, '-', $file_name);
        }
        // find file name is already exist or not
        if(file_exists($path.$file_name)){
            $file_name = rand(1000, 9999).'-'.$file_name;
        }

        return $file_name;
    }


    public static function getFileName($file){
        return basename( $file );
    }

    public static function getExtension($file){
        return pathinfo( $file , PATHINFO_EXTENSION );
    }

    public static function getFileSize($src){
        $ch = curl_init($src);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); //not necessary unless the file redirects (like the PHP example we're using here)
        $data = curl_exec($ch);
        curl_close($ch);
        if (preg_match('/Content-Length: (\d+)/', $data, $matches)) {
            $contentLength = (int)$matches[1];
            return $contentLength;
        }
        return false;
    }

    public static function downloadFile($src , $file_path){
        if(file_put_contents($file_path, file_get_contents($src)) !== false){
            return true;
        }
        return false;
    }

    public static function isFullURL($url){
        if(strpos($url, 'http') === false){
            return false;
        }
        return true;
    }

    public static function checkFirstSlash($url){
        if(strpos($url, '/') === 0){
            $url = substr($url, 1);
        }
        return $url;
    }

    public static function generateFolder($path){

        // check direckdir is exist or not
        if(!is_dir($path)){
            mkdir($path, 0777, true);
        }
    }
}
