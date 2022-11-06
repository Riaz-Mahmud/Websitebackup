<?php

namespace Backdoor\WebsiteBackup;

use Backdoor\WebsiteBackup\WebsiteBackupHelper;
use Illuminate\Support\Facades\Log;

class WebsiteBackup {

    protected $url = NULL;
    protected $path = NULL;

    protected $data = [
        'error' => false,
        'message' => '',
        'path' => '',
    ];

    public function backup($url , $path){

        $this->url = $url;
        $this->path = $path;

        $baseUrl = WebsiteBackupHelper::getDomain( $url );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $status = http_response_code( curl_getinfo($ch, CURLINFO_HTTP_CODE) );
        if ($status != 200) {
            $this->data['error'] = true;
            $this->data['message'] = "Site is not available or not found. Please check the URL. ErrorCode: $status";
            return $this->data;
        }

        WebsiteBackupHelper::generateFolder($this->path);

        // return $this->data;

        $output = curl_exec($ch);

        $dom = new \DOMDocument();
        @$dom->loadHTML($output);

        $images = $dom->getElementsByTagName('img');
        if($images->length > 0){
            $folderName = $path.'/'.'images/';
            WebsiteBackupHelper::generateFolder($folderName);
            $this->imageDownload($images , $baseUrl , $folderName);
        }

        $links = $dom->getElementsByTagName('link');
        if($links->length > 0){
            $folderName = $path.'/'.'css/';
            WebsiteBackupHelper::generateFolder($folderName);
            $this->cssDownload($links , $baseUrl , $folderName);
        }

        $scripts = $dom->getElementsByTagName('script');
        if($scripts->length > 0){
            $folderName = $path.'/'.'js/';
            WebsiteBackupHelper::generateFolder($folderName);
            $this->jsDownload($scripts , $baseUrl , $folderName);
        }

        $videos = $dom->getElementsByTagName('source');
        if($videos->length > 0){
            $folderName = $path.'/'.'videos/';
            WebsiteBackupHelper::generateFolder($folderName);
            $this->videoDownload($videos , $baseUrl , $folderName);
        }

        $html = $dom->saveHTML();
        file_put_contents($path.'/index.html', $html);
        // Storage::disk('public')->put($path.'/index.html', $html);

        curl_close($ch);

        $this->data['error'] = false;
        $this->data['message'] = 'Website Backup Successfully';
        $this->data['path'] = $path . '/index.html';

        return $this->data;
    }

    protected function videoDownload($videos , $baseUrl , $folderName){
        foreach ($videos as $video) {
            if($video->hasAttribute('src')){
                $src = $video->getAttribute('src');
                if($src){
                    try{
                        if(WebsiteBackupHelper::isFullURL($src) === false){
                            $src = $baseUrl . WebsiteBackupHelper::checkFirstSlash($src);
                        }
                        $filename = basename($src);
                        $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
                        $file_name = pathinfo($filename, PATHINFO_FILENAME);
                        $file_name = $file_name.'.'.$file_ext;
                        $file_name = WebsiteBackupHelper::replaceFileName($file_name, $folderName);

                        $file_path = $folderName . $file_name;
                        $saveFile = WebsiteBackupHelper::downloadFile($src , $file_path);
                        if($saveFile){
                            $video->removeAttribute('src');
                            $video->setAttribute('src', '/'.$file_path);
                        }
                    }catch(\Exception $e){
                        Log::info("WebsiteBackup: video: ".$e->getMessage());
                    }
                }
            }
        }
    }

    protected function jsDownload($scripts , $baseUrl , $folderName){
        foreach ($scripts as $script) {
            if($script->hasAttribute('src')){
                $src = $script->getAttribute('src');
                if($src){
                    try{
                        if(WebsiteBackupHelper::isFullURL($src) === false){
                            $src = $baseUrl . WebsiteBackupHelper::checkFirstSlash($src);
                        }
                        $filename = basename($src);
                        $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
                        $file_name = pathinfo($filename, PATHINFO_FILENAME);
                        $file_name = $file_name.'.'.$file_ext;
                        $file_name = WebsiteBackupHelper::replaceFileName($file_name , $folderName);

                        $file_path = $folderName . $file_name;
                        $saveFile = WebsiteBackupHelper::downloadFile($src , $file_path);
                        if($saveFile){
                            $script->removeAttribute('src');
                            $script->setAttribute('src', '/'.$file_path);
                        }
                    }catch(\Exception $e){
                        Log::info("WebsiteBackup: JS: ".$e->getMessage());
                    }
                }
            }
        }
    }

    protected function cssDownload($links , $baseUrl , $folderName){
        foreach ($links as $link) {
            if($link->hasAttribute('href')){
                $href = $link->getAttribute('href');
                if($href){
                    try{
                        if(WebsiteBackupHelper::isFullURL($href) === false){
                            $href = $baseUrl . WebsiteBackupHelper::checkFirstSlash($href);
                        }
                        $filename = basename($href);
                        $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
                        $file_name = pathinfo($filename, PATHINFO_FILENAME);
                        $file_name = $file_name.'.'.$file_ext;
                        $file_name = WebsiteBackupHelper::replaceFileName($file_name , $folderName);

                        $file_path = $folderName . $file_name;
                        $saveFile = WebsiteBackupHelper::downloadFile($href , $file_path);
                        if($saveFile){
                            $link->removeAttribute('href');
                            $link->setAttribute('href', '/'.$file_path);
                        }

                    }catch(\Exception $e){
                        Log::info("WebsiteBackup: CSS: ".$e->getMessage());
                    }
                }
            }
        }
    }

    protected function imageDownload($images , $baseUrl , $folderName){
        foreach ($images as $image) {
            // save all images to a folder
            $src = WebsiteBackupHelper::findImageURl($image , $baseUrl);
            if($src != false){
                try{
                    $filename = basename($src);
                    $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
                    $file_name = pathinfo($filename, PATHINFO_FILENAME);
                    $file_name = $file_name.'.'.$file_ext;
                    $file_name = WebsiteBackupHelper::replaceFileName($file_name, $folderName);

                    $file_path = $folderName . $file_name;

                    $file_size = WebsiteBackupHelper::getFileSize($src);
                    if($file_size != false){
                        if($file_size > 1024){
                            // increase execulation time for large images
                            ini_set('max_execution_time', 300);
                        }
                    }

                    $saveFile = WebsiteBackupHelper::downloadFile($src , $file_path);
                    if($saveFile){
                        $image = WebsiteBackupHelper::replaceImageURL($image, '/'.$file_path);
                    }
                }catch(\Exception $e){
                    Log::info("WebsiteBackup: video: ".$e->getMessage());
                }
            }
        }
    }
}
