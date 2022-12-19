<?php

namespace Backdoor\WebsiteBackup;

class WebsiteBackupHelper{

    private static $folder_path = NULL;

    public static function setFolderPath($path){
        self::$folder_path = $path;
    }

    public static function get_http_response_code($url) {
        $headers = get_headers($url);
        return substr($headers[0], 9, 3);
    }

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
                $url = trim($url);
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

    public static function imageFileName($link){
        $filename = basename($link);
        $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
        $file_name = pathinfo($filename, PATHINFO_FILENAME);

        if(strpos($file_ext, '?') !== false){
            $file_ext = explode('?', $file_ext);
            $file_ext = $file_ext[0];
        }

        $image_ext = ['jpg', 'jpeg', 'png', 'apng', 'gif', 'svg', 'webp', 'bmp', 'ico', 'cur', 'tiff', 'tif', 'jfif', 'pjpeg', 'pjp', 'avif'];

        $ext = collect($image_ext)->filter(function($item) use ($file_ext){
            return strpos($file_ext, $item) !== false;
        })->first();

        if($ext){
            $file_ext = $ext;
        }

        return $file_name.'.'.$file_ext;
    }

    public static function fileName($link){
        $filename = basename($link);
        $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
        $file_name = pathinfo($filename, PATHINFO_FILENAME);
        return [
            'name' => trim($file_name),
            'ext' => trim($file_ext)
        ];
    }

    public static function replaceFileName($data, $path){
        $file_name = $data['name'];

        $list = array(' ', '(', ')', '[', ']', '{', '}', '?', '/', ':', ';', '!', '@', '#', '$', '%', '^', '&', '*', '+', '=', '|', '\\', '"', "'", '<', '>', ',','`', '~');

        foreach($list as $item){
            $file_name = str_replace($item, '-', $file_name);
        }

        if(file_exists($path.$file_name.'.'.$data['ext'])){
            $file_name = time().rand(100, 9999).'-'.$file_name;
        }

        return trim($file_name) . '.' . $data['ext'];
    }

    public static function getFileSize($src){
        try{
            $ch = curl_init($src);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); //not necessary unless the file redirects (like the PHP example we're using here)
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36');
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            if (preg_match('/Content-Length: (\d+)/', $data, $matches)) {
                $contentLength = (int)$matches[1];
                return $contentLength;
            }
            return false;
        }catch(\Exception $e){
            self::logEntry("Error: ".$e->getMessage());
            return false;
        }
    }

    public static function checkHttpWWW($src){
        if(strpos($src, 'http') === false){
            if(strpos($src, 'www') !== false){
                $src = substr($src, strpos($src, 'www'));
            }
        }else{
            if(strpos($src, 'http') !== false){
                $src = substr($src, strpos($src, 'http'));
            }
        }
        return $src;
    }

    public static function generateUUID(){
        return uniqid().date('YmdHis').rand(100, 9999);
    }

    public static function downloadFile($src, $folderName){
        $file_name_ext = self::replaceFileName(self::fileName($src), $folderName);
        $file_path = $folderName . $file_name_ext;

        $options  = array('http' => array('user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36'));
        $context  = stream_context_create($options);

        self::logEntry('Downloading file: '.$src);
        try{
            if (false !== ($data = file_get_contents ($src , false, $context))){
                return file_put_contents($file_path, $data) ? ['status' => true, 'file_path' => $file_path, 'file_name_ext' => $file_name_ext] : ['status' => false, 'file_path' => $file_path, 'file_name_ext' => $file_name_ext];
            }
            return ['status' => false, 'file_path' => $file_path, 'file_name_ext' => $file_name_ext];
        }catch (\Exception $e){
            self::logEntry("Error: ".$e->getMessage());
            return ['status' => false, 'file_path' => $file_path, 'file_name_ext' => $file_name_ext];
        }catch(\Symfony\Component\ErrorHandler\Error\FatalError $e){
            self::logEntry("Error: " . $e->getMessage());
            return ['status' => false, 'file_path' => $file_path, 'file_name_ext' => $file_name_ext];
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
        try{
            if(!is_dir($path)){
                mkdir($path, 0777, true);
            }
        }catch (\Exception $e){
            self::logEntry("Error: ".$e->getMessage());
        }
    }

    public static function logEntry($message){
        $log ='['.date("F j, Y, g:i:s a").'] ' .$message.PHP_EOL;
        file_put_contents(self::$folder_path.'/log.txt', $log, FILE_APPEND);
    }
}
