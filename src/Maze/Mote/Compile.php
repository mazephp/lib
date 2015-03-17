<?php namespace Maze\Mote;

use Maze\Mote\View;
use Maze\Cute\Debug;
use Helper;

class Compile
{
    /**
     * left
     *
     * @var const string
     */
    const left = '<?php ';

    /**
     * right
     *
     * @var const string
     */
    const right = ' ?>';

    /**
     * file
     *
     * @var string
     */
    protected $file;

    /**
     * template
     *
     * @var string
     */
    protected $template;

    /**
     * content
     *
     * @var array
     */
    protected $content;

    /**
     * update
     *
     * @var bull
     */
    protected $update = false;

    /**
     * load file
     * @param string $file
     * @param string $path
     * @param string $service
     *
     * @return mixed
     */
    public function __construct($file, $path, $service)
    {
        $this->file = $this->path($file) . '.cmp.php';

        Debug::log($this->file);

        $this->check($file, $path, $service);
    }

    /**
     * check file
     * @param string $file
     * @param string $path
     * @param string $service
     *
     * @return mixed
     */
    public function check($file, $path, $service)
    {
        $this->template = $path . $file . '.html';

        if(!is_file($this->template))
        {
            die;
        }
        
        $time = is_file($this->file) ? filemtime($this->file) : 0;

        $is_service = is_file($service);

        $this->update = defined('MAZE_COMPILE');

        if(filemtime($this->template) > $time || ($is_service && filemtime($service) > $time))
        {
            $this->update = true;
        }

        if($time == 0 || $this->update == true)
        {
            $content = file_get_contents($this->template);

            if(strpos($content, '@include:') !== false)
            {
                $this->template = $path . end(explode('@include:', $content)) . '.html';

                $content = file_get_contents($this->template);
            }
        }

        if(!empty($content) && !$is_service)
        {
            return $this->create($content);
        }
    }

    /**
     * get file
     *
     * @return string
     */
    public function file()
    {
        return $this->file;
    }

    /**
     * get template
     *
     * @return string
     */
    public function template()
    {
        return $this->template;
    }

    /**
     * path create path
     * @param string $file
     *
     * @return string
     */
    public function path($file)
    {
        return Helper::path(MAZE_PATH . 'data/compile/' . MAZE_PROJECT_NAME . '/', $file);
    }

    /**
     * get
     *
     * @return mixed
     */
    public function get()
    {
        if($this->update == false)
        {
            ob_start();

            require $this->file;

            $content = ob_get_contents();

            ob_end_clean();

            return $content;
        }
        else
        {
            return false;
        }
    }

    /**
     * load view
     *
     * @param  string $service
     * @return \Maze\Mote\View
     */
    public function load($file, $path = '')
    {
        $view = View::getInstance($file);

        $path && $view->path($path);

        $view->run();

        $file = $view->file();

        if($file)
        {
            return $this->script('require MAZE_PATH . \'' . str_replace(MAZE_PATH, '', $file) . '\'');
        }
    }

    /**
     * create
     * @param string $content
     *
     * @return string
     */
    public function create($content)
    {
        $this->update = false;

        if($this->content)
        {
            $content = implode("\n", $this->content) . "\n" . $content;
        }

        $this->write($this->assets($content));

        return $this->get();
    }

    /**
     * write
     *
     * @return mixed
     */
    public function write($content)
    {
        file_put_contents($this->file, $content);

        system('chmod -R ' . $this->file . ' 777');
    }

    /**
     * script
     * @param string $string
     *
     * @return string
     */
    public function script($string)
    {
        return self::left . $string . self::right;
    }


    /**
     * equal
     * @param string $variable
     * @param string $value
     * @param string $key
     *
     * @return string
     */
    public function equal($variable, $value, $key = '')
    {
        if(strpos($key, '$') !== false)
        {
            $variable .= '['.$key.']';
        }
        elseif($key)
        {
            $variable .= '[\''.$key.'\']';
        }

        if(is_array($value))
        {
            $value = var_export($value, true);
        }
        elseif(is_string($value) && strpos($value, '"') !== false)
        {
            $value = '\'' . $value . '\'';
        }
        return $this->script('$' . $variable . '=' . $value);
    }

    /**
     * accel
     * @param string $accel
     *
     * @return mixed
     */
    public function accel($accel)
    {
        $type = $this->strip($accel);
        # include page
        if(strpos($type, '@') !== false)
        {
            return explode('@', $type);
        }
        # include database|model
        elseif(strpos($type, 'http://') === false && strpos($type, '/') !== false && (strpos($type, '.') !== false || strpos($type, '-') !== false))
        {
            $callback = 'Maze\\Accel\\Load::get(\'' . $type . '\')';

            $this->push($type, $this->equal('accel', $callback, $type));

            return true;
        }

        return $accel;
    }

    /**
     * out echo variable
     * @param string $variable
     *
     * @return string
     */
    public function out($variable)
    {
        return $this->script('echo $' . $variable);
    }

    /**
     * each
     * @param string $replace
     * @param string $accel
     * @param string $content
     *
     * @return string
     */
    public function each($replace, $accel, $content)
    {
		if($replace)
		{
			$strip = $this->strip($replace);
			if($strip && strpos($replace, $strip) !== false)
			{
				$replace = $strip;
			}
		}

        return $this->equal('t', 'count($accel[\''.$accel.'\'])-1')
        .$this->equal('i', 0)
        .$this->script('foreach($accel[\''.$accel.'\'] as $k => $v):')
        //.$this->replace($replace, $this->out('v'), $content)
        .$content
        .$this->equal('i', '$i+1')
        .$this->script('endforeach;');
    }

    /**
     * content
     * @param string $content
     *
     * @return string
     */
    public function content($content)
    {
        $echo = ' echo ';

        $content = $this->rule($content);

        $content = $this->replace('<{', self::left . $echo, $this->replace('}>', self::right, $content));

        $array = array('foreach', 'if', 'for', 'highlight_string', 'echo', 'print_r');

        foreach($array as $k => $v)
        {
            if(strpos($content, self::left . $echo . $v) !== false)
            {
                $content = $this->replace(self::left . $echo . $v, self::left . $v, $content);
            }
        }

        return $content;
    }

    /**
     * logic
     * @param string $logic
     * @param string $string
     *
     * @return string
     */
    public function logic($logic, $string)
    {
        # 这里暂时这样判断，以后再处理多种逻辑情况的
        if(strpos($logic, '|') !== false)
        {
            list($handle, $logic) = explode('|', $logic);

            if($logic == 'foreach')
            {
                $string = '<{foreach('.$handle.' as $i => $j):}>' . $string . '<{endforeach;}>';
            }
            elseif($logic == 'if')
            {
                $string = '<{if('.$handle.'):}>' . $string . '<{endif;}>';
            }
            else
            {
                $string = '<{'.$handle.'}>' . $string . '<{'.$logic.'}>';
            }
        }
        else
        {
            $string = '<{foreach('.$logic.' as $i => $j):}>' . $string . '<{endforeach;}>';
        }
        
        return $this->content($string);
    }

    /**
     * replace
     * @param string $replace
     * @param string $accel
     * @param string $content
     *
     * @return string
     */
    public function replace($replace, $accel, $content)
    {
        if(!$replace)
        {
            return $accel;
        }
        if(strpos($content, $replace) !== false && strpos($content, $replace) !== false)
        {
            $content = str_replace($replace, $accel, $content);
        }

        return $content;
    }

    /**
     * push
     * @param string $content
     *
     * @return string
     */
    public function push($key, $content)
    {
        $this->content[$key] = $content;
    }


    /**
     * tag
     * @param string $key
     * @param string $data
     *
     * @return string
     */
    public function tag($key, $data = false)
    {
        if($data)
        {
            $result = $data[$key];
        }
        else
        {
            $result = $key;
        }
        return $result;
    }

    /**
     * handle
     * @param string $accel
     * @param string $content
     * @param string $expression
     *
     * @return string
     */
    public function handle($accel, $content, $expression = '')
    {
        $result = '';

        if(is_array($accel))
        {
            $tags = $this->strip($content);
            foreach($accel as $k => $v)
            {
                $result .= $this->replace($tags, $v, $content);
            }
        }
        else
        {
            $index = false;

            if(strpos($accel, '#') > 0 && strpos($accel, '"') === false)
            {
                list($accel, $index) = explode('#', $accel);
            }

            $method = $this->accel($accel);

            if($method === true)
            {
                $result = $this->complex($accel, $content, $index, $expression);
            }
            elseif(is_array($method))
            {
                $result = $this->load($method[1], $method[0]);
            }
            elseif(is_string($accel))
            {
                $accel = $this->content($accel);

                $result = $this->replace($this->strip($content), $accel, $content);

                if($result == $content)
                {
                    //$result = $this->replace($content, $accel, $content);
                }
            }
            else
            {
                $result = $content;
            }
        }

        return $result;
    }

    /**
     * assets
     * @param string $content
     *
     * @return string
     */
    public function assets($content)
    {
        $content = $this->replace('../css/', \Config::$global['host']['css'], $content);

        $content = $this->replace('../js/', \Config::$global['host']['js'], $content);

        $content = $this->replace('../../core/', \Config::$global['host']['core'], $content);

        return $content;
    }

    /**
     * complex
     * @param string $accel
     * @param string $content
     * @param string $index
     * @param string $expression
     *
     * @return string
     */
    public function complex($accel, $content, $index = false, $expression = '')
    {
        if($index)
        {
            $result = $this->replace($this->strip($content), $this->out('accel[\''.$accel.'\'][\''.$index.'\']'), $content);
        }
        else
        {
            if($expression)
            {
                $content = $this->replace($expression, $this->out('v'), $content);
            }
            $result = $this->each($content, $accel, $this->content($content));
        }
        

        return $result;
    }

    /**
     * rule
     * @param string $content
     *
     * @return string
     */
    public function rule($content)
    {
        if(strpos($content, 'request.') !== false)
        {
            $content = preg_replace('/request\.([a-zA-Z0-9]+)/', 'Input::get(\'$1\')', $content);
        }

        if(strpos($content, '$') !== false && strpos($content, '.') !== false)
        {
            $rule = '\$([a-zA-Z0-9]+)\.([a-zA-Z0-9.]+)';

            $content = preg_replace_callback('/' . $rule . '/i', array($this, 'rule_val'), $content);
        }

        if(strpos($content, '"+') !== false)
        {
            $content = str_replace(array('"+','+"'), array('".', '."'), $content);
        }

        if(strpos($content, '<{$') !== false)
        {
            //$content = str_replace('<{$', '<{echo $', $content);
        }

        return $content;
    }

    /**
     * rule_val
     * @param array $result
     *
     * @return string
     */
    public function rule_val($result)
    {
        if(isset($result[2]) && $result[2])
        {
            $result[2] = '$' . $result[1] . '' . preg_replace('/\.([a-zA-Z0-9]+)/', '[\'$1\']', '.' . $result[2]);

            return $result[2];
        }
        return $result[0];
    }

    /**
     * strip
     * @param string $content
     *
     * @return string
     */
    public function strip($content)
    {
        return strip_tags($content);
    }
}