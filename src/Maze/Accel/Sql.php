<?php namespace Maze\Accel;

class Sql
{
	/**
     * instance
     *
     * @var string
     */
    static protected $instance;

    /**
     * getInstance
     * 
     * @return Maze\Accel\Sql;
     */
    static public function getInstance()
    {
        if(empty(self::$instance))
        {
            self::$instance = new self();
        }

        return self::$instance->init();
    }

    /**
     * create
     * 
     * @return string
     */
    public function create($table, $struct)
    {
        $create = $primary = array();

        foreach($struct as $k => $v)
        {
            $primary[$k] = '';
            if(strpos($v,' ') !== false)
            {
                $com = explode(' ',$v);
                $v = $com[0];
                if(!empty($com[1]))
                {
                    $primary[$k] .= 'not null default \'' . $com[1] . '\'';
                }
                else
                {
                    $primary[$k] .= 'not null';
                }

                if(!empty($com[2]))
                {
                    $primary[$k] .= ' comment \'' . $com[2] . '\'';
                }
                
            }
            if($k == 'id')
            {
                $primary[$k] = 'unsigned auto_increment primary key ' . $primary[$k];
            }

            $create[] = '`' . $k . '` ' . strtoupper(str_replace('-','(',$v) . ') ' . $primary[$k] . '');// not null 
        }
        $sql    = 'DROP TABLE IF EXISTS `' . $table . '`;CREATE TABLE `' . $table . '`(' . implode(',', $create) . ')';

        return $sql;
    }

    /**
     * col
     * 
     * @return string
     */
    private function col($col)
    {
        $result = '';

        if(is_array($col))
        {
        	$array = array();
            foreach($col as $k => $v)
            {
                if(!is_numeric($k))
                {
                    $array[] = $k . ' AS ' . $v;
                }
                else
                {
                    $array[] = $v;
                }
            }
            $result = implode(' ', $array);
        }
        else
        {
            $result = $col ? $col : '*';
        }
        return $result;
    }

    /**
     * select
     * 
     * @return string
     */
    public function select($table, $col = '')
    {
        $where = '';
        if($this->where)
        {
            $where = 'WHERE ' . implode(' ', $this->where);
        }

        $sql = 'SELECT ' . $this->col($col) . ' FROM `' . $table . '` ' . $where . ' ' . $this->group . ' ' . $this->order . ' ' . $this->limit;

        $this->init();

        return $sql;
    }

    /**
     * count
     * 
     * @return string
     */
    public function count($table, $col = '')
    {
        $where = '';
        if($this->where)
        {
            $where = 'WHERE ' . implode(' ', $this->where);
        }

        if(!$col)
        {
            $col = 'count(*) as total';
        }

        $sql = 'SELECT ' . $col . ' FROM `' . $table . '` ' . $where;

        return $sql;
    }
    
    /**
     * showIndex
     * 
     * @return string
     */
    public function showIndex($table)
    {
        $sql = 'SHOW INDEX FROM `' . $table . '` ';
        
        return $sql;
    }
    
    /**
     * dropIndex
     * 
     * @return string
     */
    public function dropIndex($table, $name)
    {
        $sql = 'ALTER TABLE `' . $table . '` DROP INDEX ' . $name;
        
        return $sql;
    }

    /**
     * index
     * 
     * @return string
     */
    public function index($table, $value)
    {
        $sql = 'ALTER TABLE `' . $table . '` ADD INDEX ';

        $max = count($value)-1;

        $i = 0;
        
        foreach($value as $k => $v)
        {
            $sql .= ' ' . $k . ' (' . $v . ')';

            if($i >= $max)
            {
                $sql .= '';
            }
            else
            {
                $sql .= ',';
            }

            $i++;
        }

        return $sql;
    }

    /**
     * insert
     * 
     * @return string
     */
    public function insert($table)
    {
        $sql = 'INSERT INTO `' . $table . '` (' . implode(',', $this->col) . ') VALUES (' . implode(',', $this->value) . ')';

        $this->init();

        return $sql;
    }
    
    /**
     * inserts
     * 
     * @return string
     */
    public function inserts($table, $col, $value)
    {
        $sql = 'INSERT INTO `' . $table . '` (' . $col . ') VALUES ';

        $max = count($value)-1;

        foreach($value as $k => $v)
        {
            $sql .= '(' . $v . ')';

            if($k >= $max)
            {
                $sql .= '';
            }
            else
            {
                $sql .= ',';
            }
        }

        return $sql;
    }

    /**
     * update
     * 
     * @return string
     */
    public function update($table)
    {
    	$where = '';
        if(!$this->where)
        {
            return false;
        }
        else
        {
        	$where = 'WHERE ' . implode(' ', $this->where);
        }

        $sql = 'UPDATE `' . $table . '` SET ' . implode(',', $this->value) . ' ' . $where;

        $this->init();

        return $sql;
    }

    /**
     * delete
     * 
     * @return string
     */
    public function delete($table)
    {
    	$where = '';
        if(!$this->where)
        {
            return false;
        }
        else
        {
        	$where = 'WHERE ' . implode(' ', $this->where);
        }

        $sql = 'DELETE FROM `' . $table . '` ' . $where;

        $this->init();

        return $sql;
    }

    /**
     * init
     * 
     * @return object
     */
    public function init()
    {
        $this->where = $this->value = $this->col = array();
        $this->order = '';
        $this->group = '';
        $this->limit = '';

        return $this;
    }

    /**
     * where
     * 
     * @return string
     */
    public function where($param)
    {
        if(empty($param[2])) $param[2] = '=';

        if(empty($param[3])) $param[3] = 'and';

        $where = '`' . $param[0] . '`' . $param[2] . $param[1];

        if(!$this->where)
        {
            $this->where[] = $where;
        }
        else
        {
            $this->where[] = $param[3] . ' ' . $where;
        }
    }

    /**
     * order
     * 
     * @return string
     */
    public function order($param)
    {
        if(empty($param[1])) $param[1] = 'desc';

        $this->order = 'order by `' . $param[0] . '` ' . $param[1];
    }

    /**
     * group
     * 
     * @return string
     */
    public function group($param)
    {
        $this->group = 'group by `' . $param[0] . '`';
    }

    /**
     * limit
     * 
     * @return string
     */
    public function limit($param)
    {
        if(empty($param[1])) $param[1] = 0;

        $this->limit = 'limit ' . $param[1] . ',' . $param[0];
    }

    /**
     * reset limit
     * 
     * @return string
     */
    public function reset($param)
    {
        $this->{$param[0]} = '';
    }

    /**
     * add
     * 
     * @return string
     */
    public function add($param)
    {
        $this->col[] = '`' . $param[0] . '`';
        $this->value[] = $param[1];
    }

    /**
     * set
     * 
     * @return string
     */
    public function set($param)
    {
        $this->value[] = '`' . $param[0] . '`=' . $param[1];
    }
}