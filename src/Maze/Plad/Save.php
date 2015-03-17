<?php namespace Maze\Plad;

session_start();
class Save
{
    /**
     * key
     *
     * @var string
     */
    private $key = '';
    
    /**
     * prefix
     *
     * @var string
     */
    private $prefix = 'maze';

    /**
     * method
     *
     * @var string
     */
    private $method = 'session';

    /**
     * __construct
     * @param string $key
     * @param string $method
     * 
     * @return mixed
     */
    public function __construct($key = false, $method = 'session')
    {
		$this->key 		= $key ? $key : $this->key;
		
		$this->method 	= $method ? $method : $this->method;
		
		$this->method 	= ucwords($this->method);
		
		$this->key($key);
		
		return $this;
	}

    /**
     * add
     * @param string $key
     * @param mixed $value
     * 
     * @return mixed
     */
    public function add($key, $value)
    {
        $value = Security::encode(base64_encode(serialize($value)), $this->key);
        
        $method = '_set' . $this->method;
        
        $this->$method($key, $value);
        
        return $value;
    }

    /**
     * get
     * @param string $key
     * @param mixed $type
     * 
     * @return mixed
     */
    public function get($key, $type = false)
    {
        $method = '_get' . $this->method;
        
        $value = $this->$method($key);
        
        $type == false && $value = Security::decode($value, $this->key);
        
		$value = unserialize(base64_decode($value));
		
        return $value;
    }

    /**
     * un
     * @param string $key
     * 
     * @return mixed
     */
    public function un($key)
    {
        $method = '_unset' . $this->method;
        
        return $this->$method($key);
    }

    /**
     * key
     * @param string $key
     * 
     * @return mixed
     */
    private function key($key)
    {
        $this->key = $this->prefix . '_' . $this->method . '_' . $key;
    }

    /**
     * _setCookie
     * @param string $key
     * @param string $value
     * 
     * @return mixed
     */
    private function _setCookie($key, $value)
    {
        return setCookie($this->prefix . $key, $value, time() + 3600);
    }

    /**
     * _getCookie
     * @param string $key
     * 
     * @return mixed
     */
    private function _getCookie($key)
    {
        return $_COOKIE[$this->prefix . $key];
    }

    /**
     * _unsetCookie
     * @param string $key
     * 
     * @return mixed
     */
    private function _unsetCookie($key)
    {
        return setCookie($this->prefix . $key, false, time() - 3600);
    }

    /**
     * _setSession
     * @param string $key
     * @param string $value
     * 
     * @return mixed
     */
    private function _setSession($key, $value)
    {
        return $_SESSION[$this->prefix . $key] = $value;
    }

    /**
     * _getSession
     * @param string $key
     * 
     * @return mixed
     */
    private function _getSession($key)
    {
        return (isset($_SESSION[$this->prefix . $key]) && $_SESSION[$this->prefix . $key]) ? $_SESSION[$this->prefix . $key] : false;
    }

    /**
     * _unsetSession
     * @param string $key
     * 
     * @return mixed
     */
    private function _unsetSession($key)
    {
        unset($_SESSION[$this->prefix . $key]);
        
        return true;
    }
}
