<?php namespace Maze\Accel\Mysql;

use Maze\Accel\Sql;
use Maze\Plad\Page;
use Maze\Cute\Debug;

class Store
{
	/**
     * read
     *
     * @var Maze\Accel\Mysql\Connect
     */
    protected $read;

    /**
     * update
     *
     * @var Maze\Accel\Mysql\Connect
     */
    protected $update;

    /**
     * table
     *
     * @var string
     */
	protected $table;

    /**
     * value
     *
     * @var array
     */
    protected $value = array();

    /**
     * instance
     *
     * @var string
     */
    static protected $instance;

    /**
     * getInstance
     * 
     * @return Maze\Accel\Mysql\Store;
     */
    static public function getInstance($config)
    {
        if(empty(self::$instance))
        {
            self::$instance = new self();
        }

        self::$instance->register($config);

        return self::$instance;
    }

    /**
     * __construct
     *
     * @return mixd
     */
    public function __construct($config)
    {
		$this->register($config);
		
        $this->sql = Sql::getInstance();
    }
    
    /**
     * register
     *
     * @return mixd
     */
	private function register($config)
	{
        # read update 
        if(strpos($config['host'], ',') !== false)
        {
            $host = explode(',', $config['host']);

            $config['host'] = $host[0];

            $this->read = Connect::getInstance($config);

            $config['host'] = $host[1];

            $this->update = Connect::getInstance($config);
        }
        else
        {
            $this->read = $this->update = Connect::getInstance($config);
        }
	}

	/**
     * table
     *
     * @return object
     */
	public function table($table)
	{
        $this->table = $table;

        return $this;
	}

	/**
     * create
     *
     * @return mixed
     */
	public function create($struct)
	{
        $file = \Helper::path(MAZE_PATH . 'data/database/', $this->table);
        if(file_exists($file))
        {
            return false;
        }

        $sql = $this->sql->create($this->table, $struct);

        $this->update->query($sql);

        $this->log($sql, 'create');
        
        $data['time'] = MAZE_TIME;
        
        $data['table'] = $this->table;
        
        $data['create'] = $sql;

        file_put_contents($file, '<?php return ' . var_export($data, true) . ';');
        
        return true;
	}
	
	/**
     * create index
     *
     * @return mixed
     */
	public function index($index)
	{
		if(empty($index))
		{
			return false;
		}
		$file = \Helper::path(MAZE_PATH . 'data/database/', $this->table);
        if(!file_exists($file))
        {
            return false;
        }
        
        $data = include($file);
        
        if(isset($index['version']))
        {
			$version = $index['version'];
			
			unset($index['version']);
		}
		else
		{
			$version = 1;
		}
        
        if(empty($data['index']) || (isset($data['index']) && $data['index'] != $version))
        {
			$sql = $this->sql->showIndex($this->table);
		
			$handle = $this->update->query($sql);
			
			$info = $handle->fetchAll();
			
			if($info)
			{
				foreach($info as $k => $v)
				{
					if($v['Key_name'] != 'PRIMARY')
					{
						$sql = $this->sql->dropIndex($this->table, $v['Key_name']);
						$this->update->query($sql);
					}
				}
			}
			
			$sql = $this->sql->index($this->table, $index);

			$this->update->query($sql);

			$this->log($sql, 'index');
			
			$index['version'] = $version;
			
			$data['index'] = $index;

			file_put_contents($file, '<?php return ' . var_export($data, true) . ';');
		}
        
		

        return true;
	}
	
	/**
     * insert the default value
     *
     * @return mixed
     */
	public function inserts($value)
	{
		$file = \Helper::path(MAZE_PATH . 'data/database/', $this->table);
        if(!file_exists($file))
        {
            return false;
        }
		if(isset($value['col']) && isset($value['value']))
		{
			$sql = $this->sql->inserts($this->table, $value['col'], $value['value']);

			$this->update->query($sql);
	
			$this->log($sql, 'inserts');
			
			$data = include($file);
			
			$data['insert'] = $sql;

			file_put_contents($file, '<?php return ' . var_export($data, true) . ';');
		}

        return true;
	}

    /**
     * all
     *
     * @return array
     */
    public function all($col)
    {
        $key = false;
        if(strpos($col, '|') !== false)
        {
            $array = explode('|', $col);
            $key = $array[1];
            $col = $array[0];
        }
        $data = $this->select($col, 'fetchAll');

        if($data && $key)
        {
            $result = array();

            foreach($data as $k => $v)
            {
                if(isset($v[$key]))
                {
                    if(isset($array[2]) && isset($v[$array[2]]))
                    {
                        $result[$v[$key]] = $v[$array[2]];
                    }
                    else
                    {
                        $result[$v[$key]] = $v;
                    }
                }
            }

            return $result;
        }

        return $data;
    }

    /**
     * one
     *
     * @return array
     */
    public function one($col)
    {
        return $this->select($col);
    }

    /**
     * insert
     *
     * @return int
     */
	public function insert()
    {
    	$sql = $this->sql->insert($this->table);

        $handle = $this->update->prepare($sql);

        $handle->execute($this->value);

        $id = $this->update->id();

        $this->log($sql, $this->value);

        $this->value = array();

        return $id;
    }

    /**
     * update
     *
     * @return int
     */
	public function update()
    {
        $sql = $this->sql->update($this->table);

        $result = false;

        if($sql)
        {
            $handle = $this->update->prepare($sql);

            $handle->execute($this->value);

            $result = $handle->rowCount();

            $this->log($sql, $this->value);
        }

        $this->value = array();

        return $result;
    }

    /**
     * delete
     *
     * @return int
     */
	public function delete()
    {
        $sql = $this->sql->delete($this->table);

        $result = false;

        if($sql)
        {
            $handle = $this->update->prepare($sql);

            $handle->execute($this->value);

            $result = $handle->rowCount();

            $this->log($sql, $this->value);
        }

        $this->value = array();

        return $result;
    }

    /**
     * select
     *
     * @return array
     */
    private function select($col = '', $method = 'fetch', $type = 'select')
    {
        $sql = $this->sql->{$type}($this->table, $col);

        $handle = $this->read->prepare($sql);

        $handle->execute($this->value);

        $data = $handle->$method();

        $this->log($sql, $this->value);

        $this->value = array();

        return $data;
    }

    /**
     * page
     *
     * @return object
     */
    public function page($num, $config = array())
    {
        $this->reset('limit');

        empty($config[0]) && $config[0] = 'list';

        empty($config[1]) && $config[1] = 'current';

        empty($config[2]) && $config[2] = '';

        $page = Page::getInstance($config[1]);

        $page->template($config[0]);

        $page->link($config[2]);

        $page->total($this->select('', 'fetchColumn', 'count'));
        
        $this->limit($num, $page->offset($num));

        return $this;
    }

    /**
     * __call
     *
     * @return object
     */
    public function __call($method, $param)
    {
        if(is_array($param[0]))
        {
            foreach($param[0] as $k => $v)
            {
                $this->call($method, $v);
            }
        }
        else
        {
            $this->call($method, $param);
        }

    	return $this;
    }

    /**
     * call
     *
     * @return mixd
     */
    private function call($method, $param)
    {
        if($method == 'where' || $method == 'set' || $method == 'add')
        {
            $key = ':' . count($this->value);

            $this->value[$key] = $param[1];

            $param[1] = $key;
        }

        $this->sql->$method($param);
    }

    /**
     * log
     *
     * @return log
     */
    private function log($sql, $value)
    {
        Debug::log(array('sql' => $sql, 'value' => $value));
    }
}
