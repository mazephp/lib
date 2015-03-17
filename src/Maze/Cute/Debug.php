<?php namespace Maze\Cute;

//use ChromePhp as Tool;

class Debug
{
	static private $data;
	
	/**
     * log
     *
     * @return string
     */
	static public function log($msg)
	{
		if(\Config::$global['debug']['request'])
		{
			if(is_array($msg)) $msg = var_export($msg, true);
			self::add('log', $msg . "\r\n" . self::time()  . "\r\n" . self::memory() . "\r\n" );
		}
	}

	/**
     * runtime
     *
     * @return string
     */
	static public function runtime()
	{
		self::log('Total');
        self::log('Total' . "\r\n" . self::loadfile());
	}

	/**
     * time
     *
     * @return string
     */
	static private function time()
	{
		list($a, $b) = explode(' ', MAZE_START); 
        $s = ((float)$a + (float)$b);

		list($a, $b) = explode(' ', microtime()); 
        $e = ((float)$a + (float)$b);

        return '[time:' . ($e - $s) . 'S]';
	}

	/**
     * memory
     *
     * @return string
     */
	static private function memory()
	{
        return '[memory:' . (memory_get_usage()/1024) . 'KB]';
	}

	/**
     * loadfile
     *
     * @return string
     */
	static private function loadfile()
	{
        return '[file:' . var_export(get_included_files(), true) . ']';
	}

	/**
     * add
     *
     * @return string
     */
	static private function add($method, $msg)
	{
		//Tool::$method($msg);
		self::$data[$method][] = $msg;
	}
	
	/**
     * html
     *
     * @return string
     */
	static public function html()
	{
		$html = '<div style="position:fixed;z-index:10000;bottom:0;background:white;overflow:auto;width:100%;">';
		
		foreach(self::$data as $k => $v)
		{
			$html .= '<a style="font-size:14px;font-weight:bold;margin-left:5px;" href="javascript:;" onclick="var a = $(this).next();if(a.get(0).style.display == \'none\'){a.show();$(this).parent().height(500)}else if(a.get(0).style.display != \'none\'){a.hide();$(this).parent().height(\'auto\')}">' . $k . '</a>';
			
			$html .= '<div style="display:none;">';
			$html .= '<table border="10">';
			
			foreach($v as $i => $j)
			{
				$html .= '<tr>';
				
				$html .= '<td>' . $j . '</td>';
				
				$html .= '</tr>';
			}
			
			$html .= '</table>';
			
			$html .= '</div>';
		}
		
		$html .= '</div>';
		
		return $html;
	}
}
