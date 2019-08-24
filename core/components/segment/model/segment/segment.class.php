<?php

class segment {
    /**
     * @var modX|null $modx
     */
    public $modx = null;
    /**
     * @var array
     */
    public $config = array();
    /**
     * @var string
     */
    public $namespace = 'segment';
    /**
     * @var string
     */
    public $userId = null;
    /**
     * @var bool
     */
    public $modxUserId = true;
    /**
     * @var string
     */
    public $modxUserIdprefix = null;
    /**
     * @var string
     */
    public $anonymousId = null;


    function __construct(modX &$modx, array $config = array())
    {
        
        $this->modx =& $modx;
        $corePath = $this->modx->getOption('segment.core_path', $config, $this->modx->getOption('core_path') . 'components/segment/');
        $this->config = array_merge(array(
            'basePath' => $this->modx->getOption('base_path'),
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'snippetPath' => $corePath . 'elements/snippets/',
            'pluginPath' => $corePath . 'elements/plugin/',
        ), $config);
        $this->modxUserId = $this->getOption('use_modx_id', $config, true);
        $this->modx->addPackage('segment', $this->config['modelPath']);
        $this->autoload();
    }

    private function autoload() {
        require_once $this->config['corePath'].'model/vendor/autoload.php';
    }

    private function authenticate() {
        $writeKey = $this->getOption('write_key', $this->config, null);
        if ($writeKey) {
            class_alias('Segment', 'Analytics');
            Segment::init($writeKey);
            return true;
        }else{
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, '[Segment] No write key found.');
        }
        return false;
    }

    public function getUserId(){
        if(!$this->userId){
            $cookie = $this->cleanCookie('ajs_user_id');
            if($cookie){
                $this->anonymousId = $cookie;
            }elseif($this->modxUserId && !empty($this->modx->user) && $this->modx->user->id != 0){
                $prefix = $this->getOption('prefix_modx_id', $this->config, null);
                $this->userId = $prefix.$this->modx->user->id;
            }
        }
    }

    public function getAnonymousId(){
        if(!$this->anonymousId){
            $cookie = $this->cleanCookie('ajs_anonymous_id');
            if($cookie){
                $this->anonymousId = $cookie;
            }elseif(isset($_COOKIE[session_name()])){
                $prefix = $this->getOption('prefix_modx_id', $this->config, null);
                $this->anonymousId = $prefix.$_COOKIE[session_name()];
            }
        }
    }

    /**
     * @param string $cookie name of the cookie to clean up
     */

    private function cleanCookie($cookie = null){
        if(isset($_COOKIE[$cookie])
            && $_COOKIE[$cookie] 
            && $_COOKIE[$cookie] !== "null"){
                return str_replace('"','',str_replace('%22','',$_COOKIE[$cookie]));
        }
        return '';
    }

    /**
     * @param mixed $fields "key1==property1,key2==property2,property3" or array("key1"=>"property1", "key2"=>"property2")
     * @param array $values array("key1"=>"value1")
     */
    
    public function getProperties($fields = null, $values = array()){
        $properties = array();
        if(!is_array($fields)){
            $fieldsNew = array();
            $fields = explode(',',$fields);
            foreach($fields as $field){
                $field = explode('==',$field);
                $fiedsNew[$field[0]] = ($field[1]) ? $field[1] : $field[0];
            }
            $fields = $fieldsNew;
        }
        if(!empty($fields)){
            foreach($fields as $k => $v){
                $properties[$v] = $values[$k];
            }
            return $properties;
        }else{
            return $values;
        }
    }

    /**
     * @param string $event (required)
     * @param array $properties array("property1"=>"value1")
     */

    public function track($event = null, $properties = array()){
        if(!$this->authenticate || !$event) return false;
        $track = array('event'=>$event, 'properties'=>$properties, 'timestamp'=>mktime());
        $this->getUserId();
        if($this->userId){
            $track['userId'] = $this->userId;
        }else{
            $this->getAnonymousId();
            $track['anonymousId'] = $this->anonymousId;
        }
        return Segment::track($track);
    }

    /**
     * @param string $event (required)
     * @param string $username (required)
     * @param string $userid (required)
     */

    public function trackUser($event = null, $username = null, $userid = 0){
        if(!$this->authenticate || !$event || !$username || $userid < 1) return false;
        $track = array('event'=>$event, 'username'=>$properties, 'timestamp'=>mktime(), 'context' => array('groupId' => $userid)); 
        if($this->userId){
            $track['userId'] = $this->userId;
        }
        return Segment::track($track);
    }

    /**
     * @param array $user array of properties to identify, include "id" to set a trackable id in your system
     */

    public function identify($user = array()){
        if(!is_array($user)) return false;
        $identify = array('timestamp'=>mktime());
        $this->getUserId();
        if($this->userId){
            if($user['id'] && $this->userId != $user['id']){
                $identify['userId'] = $user['id'];
                $this->alias($this->userId, $user['id']);
            }else{
                $identify['userId'] = $this->userId;
            }
        }elseif($user['id']){
            $identify['userId'] = $user['id'];
        }else{
            $this->getAnonymousId();
            $identify['anonymousId'] = $this->anonymousId;
        }
        unset($user['id']);
        if(empty($user)) return false;
        $identify['traits'] = $user;
        return Segment::identify($identify);
    }

    /**
     * @param string $previousId (required)
     * @param string $userId (required)
     */

    public function alias($previousId = null, $userId = null){
        if($previousId && $userId){
            Segment::alias(array(
                "previousId" => $previousId,
                "userId" => $userId
            ));
        }
    }

    /**
     * @param string $key The option key to search for.
     * @param array $options An array of options that override local options.
     * @param mixed $default The default value returned if the option is not found locally or as a
     * @return mixed The option value or the default value specified.
     */
    public function getOption($key, $options = array(), $default = null)
    {
        $option = $default;
        if (!empty($key) && is_string($key)) {
            if ($options != null && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } elseif (array_key_exists("{$this->namespace}.{$key}", $this->modx->config)) {
                $option = $this->modx->getOption("{$this->namespace}.{$key}");
            }
        }
        return $option;
    }

}