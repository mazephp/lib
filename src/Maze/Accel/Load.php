<?php namespace Maze\Accel;

use Maze\Accel\Model;

class Load
{
    /**
     * database
     *
     * @var string
     */
    const database =  'database/';

    /**
     * model
     *
     * @var string
     */
    const model =  'model/';

    /**
     * accel
     *
     * @var array
     */
    protected $accel;

    /**
     * class
     *
     * @var array
     */
    protected $class;

    /**
     * data
     *
     * @var array
     */
    protected $data;

    /**
     * param
     *
     * @var array
     */
    protected $param;

    /**
     * instance
     *
     * @var string
     */
    static protected $instance;

    /**
     * load file
     *
     * @param  string  $method
     * @param  array  $param
     * @return \Maze\Accel\Load
     */
    static public function get($method, $param = array())
    {
        if(empty(self::$instance))
        {
            self::$instance = new self();
        }

        return self::$instance->init($method, $param);
    }

    /**
     * accel
     *
     * @return mixed
     */
    public function accel($key)
    {
        $file = false;
        if(strpos($key, '/') !== false)
        {
            $temp = explode('/', $key);

            $method = $temp[1];

            $file = $temp[0];
        }
        else
        {
            $method = $key;
        }
    	$file = $file ? $file : MAZE_PROJECT_NAME;

    	$this->accel[$key] = array
    	(
    		'method' => $method,
    		'file'	 => $file,
    		'path'	 => MAZE_PATH . $file . '/'
    	);
    }

    /**
     * __construct
     *
     * @return mixed
     */
    private function init($key, $param = array())
    {
        $state = false;

        if(isset($this->param[$key]) && $this->param[$key] != $param)
        {
            $state = true;
        }

        $this->param[$key] = $param;

    	if(empty($this->accel[$key]))
    	{
    		$this->accel($key);
    	}

    	if($state == true || empty($this->data[$key]))
    	{
    		$this->import($key);
    	}

    	return $this->data[$key];
    }

    /**
     * handle
     *
     * @return mixed
     */
	private function import($key)
	{
		if(strpos($this->accel[$key]['method'], '-') !== false)
		{
            $method = explode('-', $this->accel[$key]['method']);

			$class = $this->accel[$key]['file']  . '_' . $method[0];

			if(empty($this->class[$class]))
			{
				$config = include($this->accel[$key]['path'] . self::database . $method[0] . '.php');

                $config['project'] = $this->accel[$key]['file'];

				$this->class[$class] = new Model($config);
			}

			if(isset($this->param[$key]) && $this->param[$key])
			{
				$this->data[$key] = $this->class[$class]->method($method[1], $this->param[$key]);
			}
			else
			{
				$this->data[$key] = $this->class[$class]->method($method[1]);
			}
		}
        elseif(strpos($this->accel[$key]['method'], '.') !== false)
        {
            $method = explode('.', $this->accel[$key]['method']);

            $class = ucwords($this->accel[$key]['file']) . '_' . ucwords($method[0]);

            if(empty($this->class[$class]))
            {
                $file = $this->accel[$key]['path'] . self::model . $method[0] . '.php';

                if(!is_file($file))
                {
                    \Helper::error(\Lang::get('file_exists', $file));
                }
                include_once($file);

                $this->data[$key] = $this->class[$class] = new $class();
            }

            if(isset($method[1]) && $method[1])
            {
                if($this->param && isset($this->param[$key]))
                {
                    $this->data[$key] = $this->class[$class]->$method[1]($this->param[$key]);
                }
                else
                {
                    $this->data[$key] = $this->class[$class]->$method[1]();
                }
            }
        }
		else
		{
			$this->data[$key] = false;
		}
	}
}