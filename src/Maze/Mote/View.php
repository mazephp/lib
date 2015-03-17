<?php namespace Maze\Mote;

use Maze\Plad\Exceptions;
use Maze\Mote\View;
use Maze\Mote\Compile;

class View
{
	/**
	 * templatePath
	 *
	 * @var string
	 */
	const templatePath = 'html/';

	/**
	 * servicePath
	 *
	 * @var string
	 */
	const servicePath =  'mote/';
	

	/**
	 * template
	 *
	 * @var string
	 */
	protected $template;

	/**
	 * template
	 *
	 * @var string
	 */
	protected $service;

	/**
	 * method
	 *
	 * @var string
	 */
	protected $method;

	/**
	 * content
	 *
	 * @var string
	 */
	protected $content;

	/**
	 * parse (default value is dom)
	 *
	 * @var string
	 */
	protected $parse = 'Dom';

	/**
	 * file
	 *
	 * @var string
	 */
	protected $file;

	/**
	 * compile
	 *
	 * @var \Maze\Mote\Compile
	 */
	protected $compile;

	/**
     * instance
     *
     * @var string
     */
    static protected $instance;

    /**
     * load file
     * @param  string  $file
     * 
     * @return \Maze\Mote\View
     */
    static public function getInstance($file)
    {
        if(empty(self::$instance[$file]))
        {
            self::$instance[$file] = new self($file);
        }

        return self::$instance[$file];
    }
	
	/**
	 * __construct
	 * @param string $file
	 *
	 * @return mixed
	 */
	public function __construct($file)
	{
		$this->file = $file;

		$this->path = MAZE_PROJECT_NAME . '/';

		$this->content = '';
	}

	/**
	 * page
	 * @param  string  $service
	 * 
	 * @return \Maze\Mote\View
	 */
	public function page($file, $path = '')
	{
		echo $this->load($file, $path);
	}

	/**
	 * load service
	 * @param  string $service
	 * 
	 * @return \Maze\Mote\View
	 */
	public function load($file, $path = '')
	{
		$view = View::getInstance($file);

		$path && $view->path($path);

		return $view->run();
	}

	/**
	 * path
	 * @param  string $path
	 * 
	 * @return \Maze\Mote\View
	 */
	public function path($path)
	{
		if(isset(\Config::$global['base']['template']))
		{
			$this->path = \Config::$global['base']['template'] . '/';
		}
		else
		{
			$this->path = $path . '/';
		}

		return $this;
	}

	/**
	 * parse
	 * @param  string  $parse
	 * 
	 * @return \Maze\Mote\View
	 */
	public function parse($parse)
	{
		$parse = 'Maze\\Mote\\Parse\\' . ucwords($parse);

		if(class_exists($parse))
		{
			$this->parse = new $parse($this->compile);
		}

		return $this;
	}

	/**
	 * run
	 *
	 * @return \Maze\Mote\View
	 */
	public function run()
	{
		if($this->content)
		{
			return $this->content;
		}

		return $this->template()->compile();
	}

	/**
	 * file
	 *
	 * @return string
	 */
	public function file()
	{
		if(empty($this->compile))
		{
			return '';
		}
		return $this->compile->file();
	}

	/**
	 * compile
	 *
	 * @return \Maze\Mote\View
	 */
	public function compile()
	{
		$this->compile = new Compile($this->file, $this->template, $this->service);

		$this->content = $this->compile->get();

		if($this->content)
		{
			return $this->content;
		}

		return $this->service();
	}

	/**
	 * service
	 *
	 * @return \Maze\Mote\View
	 */
	public function service()
	{
		$view = $this;

		require $this->service;

		return $this->content;
	}

	/**
	 * template
	 *
	 * @return \Maze\Mote\View
	 */
	public function template()
	{
		$this->template = \Config::$global['host']['assets'] . $this->path . self::templatePath;

		$this->service 	= MAZE_PATH . $this->path . self::servicePath . $this->file . '.php';

		return $this;
	}

	/**
	 * fetch
	 *
	 * @return mixed
	 */
	public function fetch()
	{
		$this->method[] = func_get_args();

		return $this;
	}

	/**
	 * display
	 *
	 * @return mixed
	 */
	public function display()
	{
		if($this->method)
		{
			if(is_string($this->parse))
			{
				$this->parse($this->parse);
			}

			if(is_object($this->parse))
			{
				$callback = function($param, $key)
				{
					$this->parse->make($param);
				};

				array_walk($this->method, $callback);

				$this->content = $this->compile->create($this->parse->get());
			}
		}
	}
}
