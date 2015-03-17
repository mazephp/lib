<?php namespace Maze\Mote\Parse;

use Maze\Mote\Parse;
use Maze\Mote\Compile;
use Sunra\PhpSimple\HtmlDomParser;

class Dom implements Parse
{
    /**
     * current
     *
     * @var object
     */
    protected $current;

    /**
     * accel
     *
     * @var string
     */
    protected $accel;

    /**
     * expression
     *
     * @var string
     */
    protected $expression;

    /**
     * attr
     *
     * @var string
     */
    protected $attr = 'outertext';

    /**
     * compile
     *
     * @var \Maze\Mote\Compile
     */
    protected $compile;

    /**
     * dom
     *
     * @var \Sunra\PhpSimple\HtmlDomParser
     */
    protected $dom;

    /**
     * __construct
     *
     * @return mixed
     */
    public function __construct(Compile $compile)
    {
        $this->compile = $compile;
        
        $this->load($this->compile->template());
    }

    /**
     * load file
     * @param string $file
     *
     * @return mixed
     */
    public function load($file)
    {
        $this->dom = HtmlDomParser::file_get_html($file);

        $this->filter();

        $this->import();
    }



    /**
     * make
     * @param array $param
     *
     * @return mixed
     */
    public function make($param)
    {
        $this->current($param[0]);

        $this->accel = $param[1];

        $this->expression = $this->current->innertext;

        if(isset($param[2])) $this->child($param[2]);

        $this->handle();
    }

    /**
     * get
     *
     * @return string
     */
    public function get()
    {
        return $this->dom->save();
    }

    /**
     * child
     * @param array $child
     *
     * @return array
     */
    public function child($child)
    {
        if($child)
        {
            $this->expression = '';

            foreach($child as $k => $v)
            {
                if($k == 'self')
                {
                    $this->attribute($v, $this->current);
                }
                else
                {
                    if(strpos($k, '|') !== false)
                    {
                        list($k, $index) = explode('|', $k);
                    }
                    else
                    {
                        $index = 0;
                        if(isset($v['key']))
                        {
                            $index = $v['key'];unset($v['key']);
                        }
                    }
                    
                    $this->attribute($v, $this->current->find($k, $index));
                }
            }
        }
    }

    /**
     * filter
     *
     * @return mixed
     */
    private function filter()
    {
        $dom = $this->dom->find('filter');

        foreach($dom as $k => $v)
        {
            $dom[$k]->outertext = '';
        }
    }

    /**
     * import
     *
     * @return mixed
     */
    private function import()
    {
        $dom = $this->dom->find('.include');

        foreach($dom as $k => $v)
        {
            $v->outertext = $this->compile->load($v->file, $v->system);
        }
    }

    /**
     * attribute
     * @param array|string $value
     * @param object $dom
     * @param array $data
     *
     * @return mixed
     */
    private function attribute($value, $dom, $data = false)
    {
        if(is_array($value))
        {
            foreach($value as $k => $v)
            {
                if($k == 'html')
                {
                    $data = $v;
                }
                else
                {
                    $index = 0;
                    if(strpos($k, '|') !== false)
                    {
                        list($k, $index) = explode('|', $k);
                    }
                    $this->plugin($dom, $k, $v, $index);
                }
            }
        }
        else
        {
            $data = $value;
        }
        
        //if($data) $dom->outertext = $this->compile->handle($data, $dom->outertext);
        if($data) $dom->innertext = $this->compile->content($data);
    }

    /**
     * plugin
     * @param object $dom
     * @param string $attribute
     * @param string $value
     * @param int $index
     *
     * @return mixed
     */
    private function plugin($dom, $attribute, $value, $index = 0)
    {
        if(is_array($value))
        {
            $key = '{data}';

            $child = $dom->find($attribute, $index);

            if(!$child) return;

            foreach($value as $k => $v)
            {
                if($k != $key)
                {
                    $this->plugin($child, $k, $v);
                }
            }

            if(isset($value[$key]))
            {
                $dom->innertext = $this->compile->logic($value[$key], $child->outertext);
            }
            
            return;
        }

        if($attribute == 'html')
        {
            $attribute = 'innertext';
        }
        # modal
        elseif($attribute == 'modal')
        {
            $dom->{'data-am-modal'} = '{target: \'#maze_modal\', closeViaDimmer: 0}';

            $attribute = 'onclick';

            if(strpos($value, '|') !== false)
            {
                $temp = explode('|', $value);
                $dom->{'data-modal-title'} = $temp[0];
                $dom->{'data-modal-content'} = $temp[1];
            }
            else
            {
                $dom->{'data-modal-title'} = 'title';
                $dom->{'data-modal-content'} = $value;
            }
            $value = '$(\'#maze_modal_title\').html($(this).attr(\'data-modal-title\'));$(\'#maze_modal_body\').html($(this).attr(\'data-modal-content\'))';
        }
        
        $value = $this->compile->content($value);

        if(strpos($attribute, '++') !== false)
        {
            $attribute = str_replace('++', '', $attribute);

            if(!strstr($dom->$attribute, $value))
            {
                $dom->$attribute = $dom->$attribute . $value;
            }
        }
        elseif(strpos($attribute, '--') !== false)
        {
            $attribute = str_replace('--', '', $attribute);

            if(strpos($dom->$attribute, $value) !== false)
            {
                $dom->$attribute = str_replace($value, '', $dom->$attribute);
            }
        }
        else
        {
            $dom->$attribute = $value;
        }
    }

    /**
     * parse
     * @param string $parse
     *
     * @return mixed
     */
    private function parse(& $parse)
    {
        if(strpos($parse, '@') !== false)
        {
            $temp = explode('@', $parse);
            $parse = $temp[0];
            $this->attr = $temp[1];
        }
    }

    /**
     * current
     * @param array $parse
     *
     * @return mixed
     */
    private function current($parse)
    {
        $this->attr = 'outertext';

        if(is_array($parse))
        {
            $this->parse($parse[0]);

            $this->current = $this->dom->find($parse[0], $parse[1]);
        }
        else
        {
            $this->parse($parse);

            $dom = $this->dom->find($parse);

            if($dom)
            {
                foreach($dom as $k => $v)
                {
                    if($k == 0)
                    {
                        $this->current = $v;
                    }
                    else
                    {
                        $dom[$k]->outertext = '';
                    }
                }
            }
            else
            {
                \Helper::error(\Lang::get('dom_exists', $parse));
            }
        }

        return $this;
    }

    /**
     * handle
     *
     * @return array
     */
    private function handle()
    {
        $this->current->{$this->attr} = $this->compile->handle($this->accel, $this->current->{$this->attr}, $this->expression);
    }
}
