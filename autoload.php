<?php

class Helper
{

	/**
     * global
     *
     * @var array
     */
    static public $global;

	/**
     * lace
     * @param string $value
     * @param string $index
     *
     * @return array
     */
	static public function lace($value, $index)
	{
		$value = explode(',', $value);

		if($index%2 == 0)
		{
			return $value[0];
		}
		else
		{
			return $value[1];
		}
	}

	/**
     * first
     * @param string $value
     * @param string $index
     *
     * @return array
     */
	static public function first($value, $index)
	{
		if($index == 0)
		{
			return $value;
		}
	}

	/**
     * last
     * @param string $value
     * @param string $index
     * @param string $total
     *
     * @return array
     */
	static public function last($value, $index, $total)
	{
		if($index == $total)
		{
			return $value;
		}
	}

	/**
     * table
     * @param string $thead
     * @param string $tbody
     * @param string $class
     *
     * @return array
     */
	static public function table($thead, $tbody, $class = '')
	{
		$result = '<table class='.$class.'><tr>';

		foreach($thead as $k => $v)
		{
			$result .= '<td style=\'width:50%\'>' . $v . '</td>';
		}

		$result .= '</tr>';

		foreach($tbody as $k => $v)
		{
			$result .= '<tr><td>' . $k . '</td><td>' . $v . '</td></tr>';
		}

		$result .= '</table>';

		return $result;
	}

	
	/**
     * path
     * @param string $path
     * @param string $file
     *
     * @return array
     */
	static public function path($path, $file = '')
	{
		if($file && strpos($file, '/') !== false)
        {
            $array = explode('/', $file);
            $count = count($array)-2;
            for($i = 0; $i <= $count; $i++)
            {
                $path .= $array[$i] . '/';
                if(!is_dir($path))
                {
                    mkdir($path);
                    system('chmod -R 777 ' . $path);
                }
            }
            $path .= $array[$i];
        }
        else
        {
        	if(!is_dir($path))
            {
                mkdir($path);
                system('chmod -R 777 ' . $path);
            }
            $path .= $file;
        }

        return $path;
	}

	/**
     * out
     * @param string $msg
     * @param int $print
     *
     * @return string
     */
	static public function out($msg, $print = true)
	{
		$json 		= Input::get('json', false);
		$callback 	= Input::get('callback', false);
		$function 	= Input::get('function', false);

		if(is_string($msg))
		{
			$result['status'] 	= 1;
			$result['msg'] 		= $msg;
		}
		else
		{
			$result = $msg;
		}

		$result = json_encode($result);

		$callback != false	&& $result = $callback . '('.$result.')';
		$function != false  && $result = '<script>parent.'.$function.'('.$result.')'.'</script>';

		if($print == true)
		{
			print_r($result);die;
		}

		return $result;
	}

	/**
     * out
     * @param string $msg
     *
     * @return string
     */
	static public function error($msg)
	{
		$send['msg'] = $msg;
		$send['status'] = 2;
		self::out($send);
	}

	/**
     * page
     * @param string $value
     *
     * @return array
     */
	static public function page()
	{
		return Maze\Plad\Page::getInstance(func_get_arg(0))->handle();
	}

	/**
     * page total
     * @param string $value
     *
     * @return array
     */
	static public function total()
	{
		return Maze\Plad\Page::getInstance(func_get_arg(0))->total();
	}
}

class Config
{
	/**
     * global
     *
     * @var array
     */
    static public $global;

	/**
     * get
     * @param string $type
     *
     * @return array
     */
	static public function get($type = 'host', $path = 'config', $project = '')
	{
		if(empty(self::$global[$type]))
		{
			$project = $project ? $project : MAZE_PROJECT_NAME;
			$path .= '/';
			if($type == 'host' || $type == 'database' || $type == 'debug')
			{
				$path .= $_SERVER['SERVER_NAME'] . '/';
			}

			self::$global[$type] = array();

			$file = MAZE_PATH . $path . $type . '.php';
			if(is_file($file))
			{
				$array = include($file);
				
				if(is_array($array))
				{
					self::$global[$type] = array_merge(self::$global[$type], $array);
				}
			}

			$file = MAZE_PROJECT_PATH . $path . $type . '.php';
			if(is_file($file))
			{
				$array = include($file);
				if(is_array($array))
				{
					self::$global[$type] = array_merge(self::$global[$type], $array);
				}
			}
			
			# 新增后台管理控制，修改后只影响本项目，
			$file = MAZE_PATH . 'data/' . $project . '/' . $path . $type . '.php';
			if(is_file($file))
			{
				$array = include($file);
				if(is_array($array))
				{
					self::$global[$type] = array_merge(self::$global[$type], $array);
				}
			}
		}
		return self::$global[$type];
	}
}

class Lang
{
	/**
     * get
     * @param string $type
     *
     * @return array
     */
	static public function get($key = 'host', $param = '')
	{
		$name = 'lang/' . Config::$global['base']['lang'];
		\Config::get($name);

		if(isset(Config::$global[$name][$key]) && $param)
		{
			if(is_string($param))
			{
				$param = array($param);
			}
			foreach($param as $k => $v)
			{
				$k = '{' . $k . '}';
				if(strpos(Config::$global[$name][$key], $k) !== false)
				{
					Config::$global[$name][$key] = str_replace($k, $v, Config::$global[$name][$key]);
				}
			}

			return Config::$global[$name][$key];
		}

		return $key;
	}
}


class Url
{
	/**
     * config
     *
     * @var array
     */
    static public $config;

	/**
     * link
     * @param string $value
     *
     * @return array
     */
	static public function get($value = false, $state = false)
	{
		if($state == true)
		{
			return 'http://' . $value . '.' . Config::$global['host']['domain'] . '/';
		}
		
		if($value === false)
		{
			return Config::$global['host']['base'] . Config::$global['url'];
		}
		
		$key = $value;

		if(isset(self::$config['url']) && isset(self::$config['url'][$key]))
		{
			return self::$config['url'][$key];
		}

		Config::get('route');

		if(Config::$global['route'])
		{
			if(strpos($value, '?') !== false)
			{
				 $arg = explode('?', $value);
				 $value = $arg[0];
			}

			if($uri = array_search($value, Config::$global['route']))
			{
				$value = $uri;
			}
			elseif(isset($arg[1]) && $arg[1])
			{
				parse_str($arg[1], $out);

				$str = $pre = '';
				$i = 1;
				self::$config['link_key'] = self::$config['link_value'] = array();
				foreach($out as $k => $v)
				{
					if($i > 1)
					{
						$pre = '&';
					}
					$str .= $pre . $k . '=$' . $i;
					$i++;
					
					self::$config['link_key'][] = $k;
					self::$config['link_value'][] = $v;
				}
				
				self::$config['link_index'] = 0;

				$result = '';

				if($key = array_search($value . '?' . $str, Config::$global['route']))
				{
					$result = preg_replace_callback('/\(.*?\)/', 'self::link', $key);
				}

				if(!$result || $result == $value)
				{
					$value = $value . '?' . $arg[1];
				}
				else
				{
					$value = $result;
				}
			}
		}

		Config::get('host');

		return self::$config['url'][$key] = Config::$global['host']['base'] . $value;
	}

	static private function link($param)
	{
		if(isset($param[0]) && $param[0] && isset(self::$config['link_value']) && isset(self::$config['link_value'][self::$config['link_index']]))
		{
			/*
			link encode
			$config = Helper::config('link_encode');
			if($config && is_numeric(self::$config['link_value'][self::$config['link_index']]) && in_array(self::$config['link_key'][self::$config['link_index']], $config))
			{
				self::$config['link_value'][self::$config['link_index']] = link_encode(self::$config['link_value'][self::$config['link_index']]);
			}
			*/
			$param[0] = self::$config['link_value'][self::$config['link_index']];
		}
		self::$config['link_index']++;
		
		return $param[0];
	}

	/**
     * location
     * @param string $value
     *
     * @return mixed
     */
	static public function location($value, $type = 1)
	{
		switch($type)
		{
			case 2 :
				$html = '<script>location.href="' . $value . '"</script>';
				Helper::out($html);
				break;

			default : 
				header('Location: ' . $value);
				break;
		}
	}

}

class Load
{
	static public function get($method, $param = array())
	{
		return Maze\Accel\Load::get($method, $param);
	}
}

class Input
{
	/**
     * get
     * @param string $name
     *
     * @return array
     */
	static public function get($name, $value = '')
	{
		if(isset($_GET[$name]))
		{
			$value = $_GET[$name];
		}
		elseif(isset($_POST[$name]))
		{
			$value = $_POST[$name];
		}
		elseif(isset($_FILES[$name]))
		{
			$value = $_FILES[$name];
		}

		return $value;
	}

	/**
     * sest
     * @param string $name
     *
     * @return array
     */
	static public function set($name, $value = '')
	{
		if($name == 'all')
		{
			$_GET += $value;
			$_POST += $value;
		}
		else
		{
			$_GET[$name] = $_POST[$name] = $value;
		}

		return $value;
	}
}