#!/usr/bin/php
<?php
class Shutter {
    protected $executable = '/usr/bin/shutter';
    protected $outputFile = null;
    protected $handlers = [
        'error' => [
            'functions' => []
        ],
        'preUpload' => [
            'functions' => []
        ],
        'uploadSuccess' => [
            'functions' => []
        ]
    ];
    protected $outputUrl = '';
    protected $error = false;
    protected $errorMessage = null;

    public function __construct(array $config){
        $this->executable = $config['shutter_path'];
    }

    public function getOutputUrl(){
        return $this->outputUrl;
    }

    public function getErrorMessage(){
        return $this->errorMessage;
    }

    public function init(){
        $this->error = false;
        $this->outputFile = null;
        $this->errorMessage = null;
        return $this;
    }

    public function save($window=null){
        if($this->outputFile === null){
            $this->outputFile = $this->generateOutputFile();
        }
        $cmd = $this->executable . ' --output=' . $this->outputFile . 
            ' ' . ($window ? '--window=' . $window : '--full')  .
            ' ' . '--exit_after_capture' .
            ' ' . '--no_session' . 
            ' ' . '--remove_cursor' .
            ' ' . '2>/dev/null'
        ;
        $output = shell_exec($cmd);
        return $this;
    }

    protected function trigger($event){
        foreach($this->handlers as $eventName => $functions){
            if($eventName == $event){
                foreach($functions as $index => $func){
                    foreach($func as $fIndex => $f){
                        $f($this);
                    }
                }
            }
        }
        return $this;
    }

    public function handler($event,$func){
        $this->handlers[$event]['functions'][] = $func;
        return $this;
    }

    public function getOutputFile(){ return $this->outputFile; }
    public function setOutputFile($f){ $this->outputFile = $f; }

    public function generateOutputFile(){
        return '/tmp/shutter-' . uniqid() . '.png';
    }

    public function upload($url){
        $file_name_with_full_path = curl_file_create(realpath($this->getOutputFile()),'image/png','foobar.png');
        $post = array('file_contents'=>$file_name_with_full_path);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $result= json_decode(curl_exec ($ch));
        curl_close ($ch);
        if($result->status == 'error'){
            $this->error = true;
            $this->errorMessage = $result->message;
            $this->trigger('error');
        }else{
            $this->error = false;
            $this->outputUrl = $result->url;
            $this->trigger('uploadSuccess');
        }
        return $this;
    }
}

$config = require('config.php');
$window = null;
if($argc == 2){
    $window = $argv[1];
}
(new Shutter($config))->handler('error',function($shutterObject){
        print("An error has occurred: " . $shutterObject->getErrorMessage());   
    })
    ->handler('uploadSuccess',function($shutterObject){
        shell_exec(sprintf($config['success_cmd'],$shutterObject->getOutputUrl()))
    })
    ->save($window)
    ->upload($config['upload_url'])
    ;
?>
