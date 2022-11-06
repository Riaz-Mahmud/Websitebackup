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

        $attribute = array('src', 'href', 'data-src', 'data-href', 'srcset', 'data-bg');

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
        $attribute = array('src', 'href', 'data-src', 'data-href', 'srcset' ,'data-bg');

        foreach($attribute as $attr){
            if($image->hasAttribute($attr)){
                $image->removeAttribute($attr);
                $image->setAttribute($attr, $path);
            }
        }
        return $image;
    }

    public static function fileName($link){
        $filename = basename($link);
        $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
        $file_name = pathinfo($filename, PATHINFO_FILENAME);
        return $file_name.'.'.$file_ext;
    }

    public static function replaceFileName($filename, $path){
        $file_name = $filename;

        $list = array(' ', '(', ')', '[', ']', '{', '}', '?', '/', ':', ';', '!', '@', '#', '$', '%', '^', '&', '*', '+', '=', '|', '\\', '"', "'", '<', '>', ',','`', '~');

        foreach($list as $item){
            $file_name = str_replace($item, '-', $file_name);
        }

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

    public static function checkBlankSpace($src){
        $orgSrc = $src;
        $data = [
            'url' => $src,
            'status' => false,
        ];
        if(substr($src, -1) == ' '){
            $data['url'] = substr($src, 0, -1);
            $data['status'] = true;
            return $data;
        }
        return $data;
    }

    public static function downloadFile($src , $file_path){

        $status = true;
        while($status){
            $blankSpace = self::checkBlankSpace($src);
            $status = $blankSpace['status'];
            $src = $blankSpace['url'];
        }

        $options  = array('http' => array('user_agent' => 'custom user agent string'));
        $context  = stream_context_create($options);

        $src = str_replace(' ', '%20', $src);

        $file_size = self::getFileSize($src);
        if($file_size != false){
            if($file_size > 1024){
                ini_set('max_execution_time', 300);
            }
            if($file_size > 5000){
                ini_set('max_execution_time', 600);
            }
            if($file_size > 10000){
                ini_set('max_execution_time', 1000);
            }
        }

        try{
            if(file_put_contents($file_path, file_get_contents($src, false, $context)) !== false){
                return true;
            }
            return false;
        }catch (\Exception $e){
            return false;
        }
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
        if(!is_dir($path)){
            mkdir($path, 0777, true);
        }
    }
}
