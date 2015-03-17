<?php namespace Maze\Accel;

use Maze\Cute\Debug;

class Model
{
	/**
     * database
     *
     * @var array
     */
	protected $database;

	/**
     * config
     *
     * @var array
     */
	protected $config;

	/**
     * db
     *
     * @var Object
     */
	protected $db;

	/**
     * __construct
     *
     * @return mixd
     */
	public function __construct($config)
	{
		$this->config = $config;

		$this->database = \Config::get('database');
	}

	/**
     * db
     *
     * @return mixd
     */
	protected function db($key = '')
	{
		if(!$key)
		{
			$key = isset($this->config['project']) ? $this->config['project'] : MAZE_PROJECT_NAME;
		}

		if(empty($this->database[$key]))
		{
			if(empty($this->database['default']))
			{
				\Helper::error(\Lang::get('core_database_exists', $key));
			}
			$this->database[$key] = $this->database['default'];
		}

		if(empty($this->db[$key]))
		{
			//$this->config['name'] = $key . '_' . $this->config['name'];
			
			$method = 'Maze\\Accel\\' . ucwords($this->database[$key]['type']) . '\\Store';

			$this->db[$key] = new $method($this->database[$key]);

			$this->db[$key]->table($key . '_' . $this->config['name']);

			# 建表
			if(isset($this->config['struct']))
			{
				if($this->db[$key]->create($this->config['struct']) == true)
				{
					# 建立索引
					if(isset($this->config['index']))
					{
						$this->db[$key]->index($this->config['index']);
					}
					
					# 写入默认值
					if(isset($this->config['default']))
					{
						$this->db[$key]->inserts($this->config['default']);
					}
				}
			}
		}

		return $this->db[$key];
	}

	/**
     * method
     *
     * @return mixd
     */
	public function method($method = 'one', $param = array())
	{
		$this->config['param'] = array();
		if(isset($this->config['request']) && isset($this->config['request'][$method]))
		{
			if($param)
			{
				$this->config['param'] = $param;
			}
			$this->config['response'] = $this->config['request'][$method];

			$this->condition(array('order', 'limit', 'page', 'group'))->push(array('where', 'add', 'set', 'option'));

			$type = isset($this->config['response']['type']) ? $this->config['response']['type'] : $method;

			$this->config['response']['col'] = isset($this->config['response']['col']) ? $this->config['response']['col'] : '';

			return $this->db()->$type($this->config['response']['col']);
		}

		return array();
	}

	/**
     * push
     *
     * @return mixd
     */
	private function push($param)
	{
		foreach($param as $k => $v)
		{
			if(isset($this->config['response'][$v]))
			{
				$value = array();
				foreach($this->config['response'][$v] as $i => $j)
				{
					$temp = $this->request($v. '_' . $i, $j);
					if($temp)
					{
						$value[] = array($i, $temp);
					}
				}

				if($value)
				{
					if($v == 'option') $v = 'where';
					
					$this->db()->$v($value);
				}
			}
		}

		return $this;
	}

	/**
     * condition
     *
     * @return mixd
     */
	private function condition($param)
	{
		foreach($param as $k => $v)
		{
			if(isset($this->config['response'][$v]))
			{
				$value = $this->config['response'][$v] ? $this->config['response'][$v] : $this->request($v, $this->config['response'][$v], '-');

				if(empty($value[1])) $value[1] = '';

				if($v == 'page')
				{
					if(is_string($value[1]))
					{
						$temp[] = $value[1];
						unset($value[1]);
						$value[1] = $temp;
					}

					if(isset($value[2])) $value[1][2] = $value[2];

					if(isset($this->config['param']['page']))
					{
						$value[1] = array_merge($value[1], $this->config['param']['page']);
					}
				}

				$this->db()->$v($value[0], $value[1]);
			}
		}

		return $this;
	}

	/**
     * request
     *
     * @return mixd
     */
	private function request($key, $value, $split = '')
	{
		$state 		= false;
		if(isset($this->config['param']) && isset($this->config['param'][$key]))
		{
			$request 	= $this->config['param'][$key];
		}
		
		if(empty($request))
		{
			$request 	= \Input::get($key, $value);
		}

		if(is_array($request))
		{
			$request = implode(',', $request);
		}

		if(strpos($value, '/') !== false)
		{
			$state = preg_match($value, $request);
		}
		elseif(!empty($request) && is_string($value) && function_exists($value))
		{
			$state = $value($request);
		}
		elseif($request)
		{
			if($split)
			{
				$request = explode($split, $request);
			}
			$state = true;
		}

		Debug::log(array('text' => 'model', 'state' => $state, 'preg' => $value, 'key' => $key, 'value' => $request));

		if($state)
		{
			return $request;
		}

		# error
		if(!strstr($key, 'option_'))
		{
			$key = end(explode('_', $key));
			
			$text = \Lang::get('core_database_request', array($key, $value));

			\Helper::error($text);
		}

		return false;
	}
}