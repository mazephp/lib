<?php namespace Maze\Plad;

class Page
{
    /**
     * total
     *
     * @var int
     */
    public $total;

    /**
     * num
     *
     * @var int
     */
    public $num;

    /**
     * maxpage
     *
     * @var int
     */
    public $maxpage;

    /**
     * page
     *
     * @var int
     */
    public $page;

    /**
     * prev
     *
     * @var int
     */
    public $prev;

    /**
     * next
     *
     * @var int
     */
    public $next;

    /**
     * current
     *
     * @var int
     */
    public $current;

    /**
     * template
     *
     * @var string
     */
    public $template;

    /**
     * html
     *
     * @var string
     */
    public $html;

    /**
     * instance
     *
     * @var string
     */
    static protected $instance;

    /**
     * getInstance
     * 
     * @return Maze\Plad\Page;
     */
    static public function getInstance($key)
    {
        if(empty(self::$instance[$key]))
        {
            self::$instance[$key] = new self();
        }

        return self::$instance[$key];
    }

    /**
     * __construct
     *
     * @return mixd
     */
    private function __construct()
    {
        $this->maxpage = 10;
    }

    /**
     * current
     *
     * @return int
     */
    public function current($name = 'page')
    {
        $this->current = \Input::get($name, 1);

        return $this->current;
    }

    /**
     * offset
     *
     * @return int
     */
    public function offset(&$num)
    {
        $this->num = $num;

        $offset = $this->num * ($this->current()-1);
        
        return $offset;
    }

    /**
     * total
     *
     * @return mixd
     */
    public function total($total = false)
    {
        if(!$total)
        {
            return $this->total ? $this->total : 0;
        }
        
        $this->total = $total;
    }

    /**
     * maxpage
     *
     * @return mixd
     */
    public function maxpage($maxpage)
    {
        $this->maxpage = $maxpage;
    }

    /**
     * link
     *
     * @return mixd
     */
    public function link($link = '')
    {
        if(!$link) $link = \Config::$global['url'];
        
        $this->link = $link;
    }

    /**
     * template
     *
     * @return mixd
     */
    public function template($template)
    {
        $this->template = $template;
    }

    /**
     * handle
     *
     * @return mixd
     */
    public function handle()
    {
        if($this->total < 1)
        {
            return '';
        }

        # total page
        $this->page = ceil($this->total / $this->num);

        # current page
        if($this->page < $this->current)
        {
            $this->current = $this->page;
        }

        if($this->page <= 1)
        {
            return '';
        }

        if($this->total > $this->num)
        {
            if($this->current > 1)
            {
                $this->prev = $this->current-1;
            }

            if($this->current < $this->page)
            {
                $this->next = $this->current+1;
            }
            

            if($this->page <= $this->maxpage)
            {
                $this->start = 1;
                $this->end   = $this->page;
            }
            else
            {
                $page = intval($this->maxpage/2);
                if($this->current < $page)
                {
                    $this->start = 1;
                }
                elseif($this->current <= ($this->page - $this->maxpage))
                {
                    $this->start = $this->current - $page;
                }
                elseif($this->current > $this->page - $this->maxpage && $this->current <= $this->page - $page)
                {
                    $this->start = $this->current - $page;
                }
                elseif($this->current > $this->page - $page)
                {
                    $this->start = $this->page - $this->maxpage + 1;
                }
                $this->end = $this->start + $this->maxpage - 1;

                if($this->start < 1)
                {
                    $this->end = $this->current + 1 - $this->start;
                    $this->start = 1;
                    if(($this->end - $this->start) < $this->maxpage)
                    {
                        $this->end = $this->maxpage;
                    }
                }
                elseif($this->end > $this->page)
                {
                    $this->start = $this->page-$this->maxpage+1;
                    $this->end      = $this->page;
                }
            }
        }

        $file = MAZE_PROJECT_PATH . 'page/' . $this->template . '.php';
        
        if(is_file($file))
        {
            $page = $this;

            include($file);

            return $page->get();
        }

        return '';
    }

    /**
     * get
     *
     * @return string
     */
    public function get()
    {
        return $this->html;
    }

    /**
     * html
     *
     * @return string
     */
    public function html($parent, $child, $prev = array('prev', 'prev'), $next = array('next', 'next'), $current = array('page','current', ''), $start = false, $end = false)
    {
        $html = '';

        if(empty($current[2]))
        {
            $current[2] = '';
        }

        if($start && $this->current > 1)
        {
            $html .= $this->set($child, $start[1], 1, $start[0], $current[2]);
        }

        if($this->prev)
        {
            # prev
            $html .= $this->set($child, $prev[1], $this->prev, $prev[0], $current[2]);
        }
        
        $i = $this->start;

        for($i; $i <= $this->end; $i++)
        {
            $class = $current[0];
            if($i == $this->current)
            {
                if($class) $class .= ' ';
                $class .= $current[1];
                
            }

            $html .= $this->set($child, $class, $i, $i, $current[2]);
        }

        if($this->next)
        {
            # next
            $html .= $this->set($child, $next[1], $this->next, $next[0], $current[2]);
        }

        if($end && $this->current < $this->end)
        {
            $html .= $this->set($child, $end[1], $this->end, $end[0], $current[2]);
        }

        $this->html = $this->tag($parent, $html);
    }

    /**
     * set
     *
     * @return string
     */
    public function set($child, $class, $num, $name, $type = '')
    {
        if($type == 'parent')
        {
            $child[1] = 'class="'.$class.'"';
            $class = '';
        }

        return $this->tag($child, $this->tag(array('a', $this->attr($class, $this->href($num))), $name));
    }

    /**
     * tag
     *
     * @return string
     */
    public function tag($tag, $content)
    {
        $attr = '';
        if(is_array($tag))
        {
            $temp = $tag;unset($tag);
            $tag = $temp[0];
            $attr = $temp[1];
        }
        return '<' . $tag . ' ' . $attr . '>' . $content . '</' . $tag . '>';
    }

    /**
     * href
     *
     * @return string
     */
    public function href($page)
    {
        return \Url::get($this->link . '&page=' . $page);
    }

    /**
     * attr
     *
     * @return string
     */
    public function attr($class, $href)
    {
        return ' class="'.$class.'" href="'.$href.'" ';
    }
}
