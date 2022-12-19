<?php

namespace Backdoor\WebsiteBackup;

use Backdoor\WebsiteBackup\WebsiteBackupHelper as Helper;

class WebsiteBackup {

    protected $url = NULL;
    protected $path = NULL;
    protected $defaultElement = 'websitebackupuuid';

    protected $data = [
        'error' => false,
        'message' => '',
        'path' => '',
    ];

    protected $elements = [
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

    protected $downloadQueue = [];
    protected $maxExecutionTime = 30;

    public function backup($url , $path){

        $this->url = $url;
        $this->path = $path;

        if(!$this->url || !$this->path){
            $this->data['error'] = true;
            $this->data['message'] = 'URL or Path is missing';
            return $this->data;
        }

        try{
            Helper::generateFolder($this->path);
            Helper::setFolderPath( $this->path );
            Helper::logEntry('Info: Backup started for '.$this->url);

            $baseUrl = Helper::getDomain( $this->url );
            Helper::logEntry('Info: Base URL: '.$baseUrl);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $status = Helper::get_http_response_code($this->url);
            Helper::logEntry('Info: '. 'Site Status: '.$status);
            if ($status != 200) {
                $this->data['error'] = true;
                $this->data['message'] = "Site is not available or not found. Please check the URL. ErrorCode: $status";
                Helper::logEntry('Error '. $this->data['message']);
                return $this->data;
            }

            $output = curl_exec($ch);

            $dom = new \DOMDocument();
            @$dom->loadHTML($output);
            set_time_limit(0);

            foreach ($this->elements as $element => $value) {
                $links = $dom->getElementsByTagName($element);
                if($links->length > 0){
                    $folderName = $path.$value['folder'];
                    Helper::generateFolder($folderName);
                    if($element == 'img' || $element == 'div'){
                        $this->image($links , $baseUrl , $folderName);
                    }else{
                        $this->common($links, $value['attribute'], $baseUrl, $folderName);
                    }
                }
            }

            $html = $dom->saveHTML();
            $html = $this->downloadContent($html);
            file_put_contents($path.'/index.html', $html);

            curl_close($ch);

            $this->data['error'] = false;
            $this->data['message'] = 'Backup created successfully';
            $this->data['path'] = $path . '/index.html';

            Helper::logEntry('Info: '. $this->data['message']. ' Path: '.$this->data['path'] );

            Helper::logEntry('Download Queue: Unsuccesful download links =>');
            foreach ($this->downloadQueue as $value) {
                if($value['status'] == 'pending'){
                    Helper::logEntry('Pending Queue: link:'. $value['url'] . ' status:'. $value['status']);
                }
            }

            set_time_limit(30);

            return $this->data;

        }catch(\Exception $e){
            $this->data['error'] = true;
            $this->data['message'] = $e->getMessage();
            Helper::logEntry('Error: '. $this->data['message']);
            set_time_limit(30);
            return $this->data;
        }
    }

    protected function downloadContent($html){
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);

        foreach ($this->elements as $element => $value) {
            $items = $dom->getElementsByTagName($element);
            if($items->length > 0){
                foreach($items as $item){
                    foreach($this->downloadQueue as $key => $queue){
                        if( $item->hasAttribute($this->defaultElement) && isset($queue['uuid']) && $item->getAttribute($this->defaultElement) == $queue['uuid'] ){
                            $saveFile = Helper::downloadFile($queue['url'] , $queue['folder']);
                            if($saveFile['status']){
                                $this->downloadQueue[$key]['status'] = 'success';
                                if($queue['type'] == 'image'){
                                    $item->removeAttribute($this->defaultElement);
                                    $item = Helper::replaceImageURL($item , '/'.$saveFile['file_path']);
                                }else{
                                    $item->removeAttribute($this->defaultElement);
                                    $item->setAttribute($queue['attribute'] , '/'.$saveFile['file_path']);
                                }
                            }
                        }
                    }
                }
            }
        }
        $html = $dom->saveHTML();
        return $html;
    }

    protected function common($list, $attribute , $baseUrl , $folderName){
        foreach ($list as $item) {
            if($item->hasAttribute($attribute)){
                $url = $item->getAttribute($attribute);
                if($url){
                    $url = str_replace(' ', '%20', trim($url));
                    try{
                        if(Helper::isFullURL($url) === false){
                            $url = $baseUrl . Helper::checkFirstSlash($url);
                        }
                        $uuid = Helper::generateUUID();

                        $this->downloadQueue[] = [
                            'url' => Helper::checkHttpWWW($url),
                            'path' => $url,
                            'folder' => $folderName,
                            'attribute' => $attribute,
                            'type' => 'common',
                            'uuid' => $uuid,
                            'status' => 'pending',
                        ];
                        $item->setAttribute($this->defaultElement, $uuid);

                    }catch(\Exception $e){
                        Helper::logEntry('Error: '.$e->getMessage());
                    }
                }
            }
        }
    }

    protected function image($images , $baseUrl , $folderName){
        foreach ($images as $image) {
            $src = Helper::findImageURl($image , $baseUrl);
            if($src != false){
                $src = str_replace(' ', '%20', trim($src));
                try{
                    $uuid = Helper::generateUUID();

                    $this->downloadQueue[] = [
                        'url' => Helper::checkHttpWWW($src),
                        'path' => $src,
                        'folder' => $folderName,
                        'type' => 'image',
                        'uuid' => $uuid,
                        'status' => 'pending',
                    ];

                    $image->setAttribute($this->defaultElement, $uuid);
                }catch(\Exception $e){
                    Helper::logEntry('Error: '. $e->getMessage());
                }
            }
        }
    }
}
