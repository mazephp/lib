<?php namespace Maze\Accel\Mysql;

use Maze\Cute\Debug;

class Connect
{
	/**
     * handle
     *
     * @var object
     */
	private $handle;

	/**
     * instance
     *
     * @var string
     */
    static protected $instance;

    /**
     * getInstance
     * 
     * @return Maze\Accel\Mysql\Connect;
     */
    static public function getInstance($config)
    {
    	$key = $config['host'] . $config['database'];
        if(empty(self::$instance[$key]))
        {
            self::$instance[$key] = new self();
            self::$instance[$key]->init($config);
        }

        return self::$instance[$key];
    }

    
    /**
     * init
     *
     * @return mixd
     */
	private function init($config)
	{
		if(strpos($config['host'], ':') !== false) list($config['host'], $config['port']) = explode(':', $config['host']);

        $dsn['type'] 		= $config['type'];
        $dsn['host'] 		= $config['host'];
        $dsn['port'] 		= $config['port'];
        $dsn['dbname'] 	    = $config['database'];
        $dsn['charset']     = $config['charset'];

        foreach($dsn as $key => $val)
        {
            $dsn[$key] = "$key=$val";
        }

        $dsnList = 'mysql:' . implode(';', $dsn);

        try
        {
            $this->handle = new \PDO($dsnList, $config['username'], $config['password']);
            //$this->handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->handle->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
            $this->handle->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            Debug::log('db ' . $config['host'] . ' connected');
        }
        catch(\PDOException $e)
        {
            if(strstr($e->getMessage(),'Unknown database'))
            {
                $link = @mysql_connect($config['host'], $config['username'], $config['password']);
                @mysql_query("CREATE DATABASE `".$config['database']."` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;", $link);
                @mysql_close($link);
                $this->init($config);
            }
            else
            {
                echo $e->getMessage();
            }
        }

        //$this->query("set names '".$config['charset']."'");
        //$this->_log('connected mysql:' . $config['host']);
	}

	/**
     * __construct
     *
     * @return mixd
     */
    public function __destruct()
    {
        $this->close();
    }

	/**
     * handle
     *
     * @return object
     */
	public function handle()
	{
		return $this->handle;
	}

	/**
     * close
     *
     * @return mixd
     */
    public function close()
    {
		$this->handle = null;
	}

	/**
     * prepare
     *
     * @return object
     */
    public function prepare($sql)
    {
    	return $this->handle->prepare($sql);
    }

    /**
     * exec
     *
     * @return object
     */
    public function exec($sql)
    {
        return $this->handle->exec($sql);
    }

    /**
     * query
     *
     * @return object
     */
    public function query($sql)
    {
        return $this->handle->query($sql);
    }

    /**
     * lastid
     *
     * @return int
     */
    public function id()
    {
        return $this->handle->lastInsertId();
    }
}