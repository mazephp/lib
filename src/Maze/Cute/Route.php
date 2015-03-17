<?php namespace Maze\Cute;

use Maze\Mote\View;
use Maze\Accel\Load;
use Maze\Cute\Debug;

class Route
{
	/**
	 * The URI pattern the route responds to.
	 *
	 * @var string
	 */
	protected $uri;
	
	/**
	 * file
	 *
	 * @var string
	 */
	protected $file;
	
	/**
	 * method
	 *
	 * @var string
	 */
	protected $method;
	
	/**
	 * param
	 *
	 * @var array
	 */
	protected $param;
	
	/**
	 * explode
	 *
	 * @var string
	 */
	protected $explode = '/';
	
	/**
	 * Run the route service and return the response.
	 *
	 * @return mixed
	 */
	public function run()
	{
		$this->initUri();
		
		$this->parseUri();

		\Config::get('host');

		\Config::$global['uri'] = $this->uri;

		if(strpos($this->uri, '-') !== false)
		{
			$this->content = Load::get($this->uri);
		}
		elseif(strpos($this->uri, '.') !== false)
		{
			$this->content = Load::get($this->uri);
		}
		else
		{
			$this->content = View::getInstance($this->file)->run();
		}
	}

	/**
	 * out
	 *
	 * @return mixed
	 */
	public function out()
	{
		if(!$this->content)
		{
			\Helper::error(\Lang::get('error_page'));
		}
		$this->debug();
		print_r($this->content);
	}
	
	/**
	 * debug
	 *
	 * @return mixed
	 */
	private function debug()
	{
		if(\Config::$global['debug']['request'])
		{
			Debug::runtime();
			if(strpos($this->content, '</body>') >= 0)
			{
				$this->content = str_replace('</body>', Debug::html(), $this->content . '</body>');
			}
			else
			{
				$this->content = $this->content.Debug::html();
			}
		}
	}

	/**
	 * parse Uri
	 *
	 * @return mixed
	 */
	public function parseUri()
	{
		$this->method = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'get';
		
		if($this->method == 'get' && !empty($_SERVER['REQUEST_URI']))
		{
			$request_uri = strtoupper(urldecode($_SERVER['REQUEST_URI']));
			if(strpos($request_uri, '<') !== false || strpos($request_uri, '"') !== false || strpos($request_uri, 'CONTENT-TRANSFER-ENCODING') !== false)
			{
				\Helper::error(\Lang::get('request_tainting'));
			}
			unset($request_uri);
		}

		if(strpos($this->uri, $this->explode) !== false)
		{
			$array = explode($this->explode, $this->uri);
			
			if(isset($array[2]) && empty($array[3]))
			{
				$this->file = $array[0] . $this->explode . $array[1] . $this->explode . $array[2];
				unset($array[0]);
				unset($array[1]);
				unset($array[2]);
			}
			elseif(isset($array[1]))
			{
				$this->file = $array[0] . $this->explode . $array[1];
				unset($array[0]);
				unset($array[1]);
			}
			elseif(isset($array[0]))
			{
				$this->file = $array[0];
				unset($array[0]);
			}
			
			$this->param = array_values($array);
		}
		else
		{
			$this->file = $this->uri;
		}
	}

	/**
	 * Set the URI that the route responds to.
	 *
	 * @return \Maze\Cute\Route
	 */
	public function initUri()
	{
		$this->uri = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], $this->explode) : ((isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : '');
		
		\Config::$global['url'] = preg_replace('/^\//i', '', $_SERVER['REQUEST_URI']);

		\Config::get('route');

		if(\Config::$global['route'] && $this->uri)
		{
			if(isset(\Config::$global['route'][$this->uri]))
			{
				$this->uri = \Config::$global['route'][$this->uri];
			}
			else
			{
				foreach(\Config::$global['route'] as $k => $v)
				{
					$k = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $k));

                    if(preg_match('#^'.$k.'$#', $this->uri))
                    {
	                    if(strpos($v, '$') !== false AND strpos($k, '(') !== false)
	                    {
	                    	$v = preg_replace('#^'.$k.'$#', $v, $this->uri);
	                    }

	                    $this->uri = $v;
                    }
				}

				//\Config::$global['url'] = $this->uri;

				if(strpos($this->uri, '?') !== false)
				{
					$temp = explode('?', $this->uri);
					$this->uri = $temp[0];
					parse_str($temp[1], $input);
					\Input::set('all', $input);
				}
			}
		}

		empty($this->uri) && $this->uri = 'home';
		
		return $this;
	}

	/**
	 * Set the URI that the route responds to.
	 *
	 * @param  string  $uri
	 * @return \Maze\Cute\Route
	 */
	public function setUri($uri)
	{
		$this->uri = $uri;

		return $this;
	}

	/**
	 * Get the URI
	 *
	 * @return string
	 */
	public function getUri()
	{
		return $this->uri;
	}
}