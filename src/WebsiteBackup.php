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

        $baseUrl = WebsiteBackupHelper::getDomain( $this->url );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $status = http_response_code( curl_getinfo($ch, CURLINFO_HTTP_CODE) );
        if ($status != 200) {
            $this->data['error'] = true;
            $this->data['message'] = "Site is not available or not found. Please check the URL. ErrorCode: $status";
            return $this->data;
        }

        WebsiteBackupHelper::generateFolder($this->path);

        $output = curl_exec($ch);

        $dom = new \DOMDocument();
        @$dom->loadHTML($output);

        $elements = [
            'img' => [
                'attribute' => 'src',
                'folder' => '/images'.'/',
            ],
            'link' => [
                'attribute' => 'href',
                'folder' => '/css'.'/',
            ],
            'script' => [
                'attribute' => 'src',
                'folder' => '/js'.'/',
            ],
            'source' => [
                'attribute' => 'src',
                'folder' => '/source'.'/',
            ],
            'div' => [
                'attribute' => 'data-bg',
                'folder' => '/div'.'/',
            ],
        ];

        foreach ($elements as $element => $value) {
            $links = $dom->getElementsByTagName($element);
            if($links->length > 0){
                $folderName = $path.$value['folder'];
                WebsiteBackupHelper::generateFolder($folderName);
                if($element == 'img' || $element == 'div'){
                    $this->imageDownload($links , $baseUrl , $folderName);
                }else{
                    $this->commonDownload($links, $value['attribute'], $baseUrl, $folderName);
                }
            }
        }

        $html = $dom->saveHTML();
        file_put_contents($path.'/index.html', $html);

        curl_close($ch);

        $this->data['error'] = false;
        $this->data['message'] = 'Backup created successfully';
        $this->data['path'] = $path . '/index.html';

        return $this->data;
    }

    protected function commonDownload($list, $attribute , $baseUrl , $folderName){
        foreach ($list as $item) {
            if($item->hasAttribute($attribute)){
                $url = $item->getAttribute($attribute);
                if($url){
                    try{
                        if(WebsiteBackupHelper::isFullURL($url) === false){
                            $url = $baseUrl . WebsiteBackupHelper::checkFirstSlash($url);
                        }

                        $file_path = $folderName . WebsiteBackupHelper::replaceFileName( WebsiteBackupHelper::fileName($url), $folderName);
                        $saveFile = WebsiteBackupHelper::downloadFile($url , $file_path);
                        if($saveFile){
                            $item->removeAttribute($attribute);
                            $item->setAttribute($attribute, '/'.$file_path);
                        }
                    }catch(\Exception $e){
                        Log::error($e->getMessage());
                    }
                }
            }
        }
    }

    protected function imageDownload($images , $baseUrl , $folderName){
        foreach ($images as $image) {
            $src = WebsiteBackupHelper::findImageURl($image , $baseUrl);
            if($src != false){
                try{
                    $file_path = $folderName . WebsiteBackupHelper::replaceFileName(WebsiteBackupHelper::fileName($src), $folderName);
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
