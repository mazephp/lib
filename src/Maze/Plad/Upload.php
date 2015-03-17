<?php namespace Maze\Plad;

class Upload
{
	/**
	 * base path
	 *
	 * @var string
	 */
	const path = 'data';
	
    /**
     * config
     *
     * @var array
     */
    private $config = array();

    /**
     * data
     *
     * @var array
     */
    private $data = array();

    /**
     * __construct
     * 
     * @return mixed
     */
    public function __construct()
    {}

    /**
     * save
     * 
     * @return mixed
     */
    public function save($config)
    {
        $this->config  = $config;
        $this->make();
        return $this->copy();
    }

    /**
     * data
     * 
     * @return array
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * create file name
     * 
     * @return int
     */
    private function file()
    {
        //return md5(microtime()+rand()*100);
        
        $ext = '.jpg';
        
        if($this->data['tmp']['type'] == 'image/gif')
        {
			$ext = '.gif';
		}
		if(empty($this->config['filename']))
		{
			$filename = md5($this->data['tmp']['name']);
			$this->data['filename'] = $filename . $ext;
		}
		else
		{
			$this->data['filename'] = $this->config['filename'];
		}
    }
    
    /**
     * create path
     * 
     * @return mixed
     */
    private function create($path)
    {
		if(is_array($path))
		{
			foreach($path as $v)
			{
				$this->create($v);
			}
		}
		else
		{
			if(isset($this->config['id']) && $this->config['id'] > 0)
			{
				$id = ceil($this->config['id']/1000);
				
				$filepath = \Helper::path(MAZE_PATH . self::path, $path . $id . '/');
			}
			else
			{
				$filepath = \Helper::path(MAZE_PATH . self::path, $path . date("Y") . '/' . date("m") . '/' . date("d") . '/');
			}
			
			$this->data[$path . '_file'] = $filepath . $this->data['filename'];
		}
    }

    /**
     * make file
     * 
     * @return mixed
     */
    private function make()
    {
        $this->checkConfig();
        
        $this->file();
        
        $this->create(array('source', 'view'));
        
        $this->data['tmp']     = $this->post($this->config['name']);
    }

    /**
     * copy file
     * 
     * @return mixed
     */
    private function copy()
    {
        if(isset($this->config['filesize']) && $this->config['filesize'] > 0 && $this->data['tmp']['size'] > $this->config['filesize'])
        {
            return $this->error('file size error -1');
        }
        if(isset($this->config['filelimit']) && strstr($this->config['filelimit'], '*'))
        {
            $imgstream = file_get_contents($this->data['tmp']['tmp_name']);
            $im = imagecreatefromstring($imgstream);

            $width = imagesx($im);
            $height = imagesy($im);

            @imagedestroy($im);
            $attribute = explode(',', $this->config['filelimit']);
            $array = explode('*', $attribute[0]);

            if($width > $array[0])
            {
                return $this->error('file max width error -2');
            }
            if($height > $array[1])
            {
                return $this->error('file max height error -3');
            }

            if(isset($attribute[1]))
            {
                $array = explode('*', $attribute[1]);
                if($width < $array[0])
                {
                    return $this->error('file min width error -4');
                }
                if($height < $array[1])
                {
                    return $this->error('file min height error -5');
                }
            }
        }

        if($this->data['type'] && !strstr($this->data['type'], $this->data['tmp']['type']))
        {
            return $this->error('upload type error -6');
        }

        if(!copy($this->data['tmp']['tmp_name'], $this->data['file']))
        {
            return $this->error('upload error -7');
        }
        else
        {
			# 复制一份用来给用户看的，我们保留一份吧
			copy($this->data['file'], $this->data['view_file']);
			//@unlink($this->data['tmp']['tmp_name']);
            $this->data['name'] = $this->data['tmp']['name'];
            $this->data['type'] = $this->data['tmp']['type'];

            if(isset($this->config['width']) && $this->config['width'])
            {
                $imgstream = file_get_contents($this->data['file']);
                $im = imagecreatefromstring($imgstream);

                $this->data['width'] = imagesx($im);
                $this->data['height'] = imagesy($im);

                @imagedestroy($im);
            }
            $img = false;

            //图片压缩
            if(isset($this->config['compress']))
            {
                $img = \Helper::core('img');
                $img->compress($this->data['file'], $this->config['compress']);
            }
            
            //添加水印
            if(isset($this->config['mark']))
            {
                if(!$img)
                {
                    $img = \Helper::core('img');
                }
                $img->mark($this->data['file'], $this->config['mark']);
            }

            //建立小图
            if(isset($this->config['thumb']))
            {
                if(!$img)
                {
                    $img = \Helper::core('img');
                }
                $img->thumb($this->data['file'], $this->config['thumb']);
            }
            //建立小图
            if(isset($this->config['crop']))
            {
                if(!$img)
                {
                    $img = \Helper::core('img');
                }
                $img->crop($this->data['file'], $this->config['crop']);
            }

            return $this->data;
        }
    }

    /**
     * @desc 检测是否设置了配置
     * @param *
     * @author leo(suwi.bin)
     * @date 2012-03-23
     */
    private function checkConfig()
    {
        if(!$this->config)
        {
            $this->error('config error');
        }
        if(!isset($this->config['name']))
        {
            $this->error('name error');
        }
        if(!isset($this->config['filetype']))
        {
            $this->config['filetype'] = 'file';
        }
        if(isset($this->config['filepath']) && $this->config['filepath'])
        {
            $this->config['filepath'] .= '/';
        }

        $this->data['type'] = false;
        switch($this->config['filetype'])
        {
            case 'file':
                $this->data['type'] = 'image/png,image/x-png,image/jpg,image/jpeg,image/pjpeg,image/gif,image/bmp,application/javascript,text/css,application/octet-stream';
                break;
            case 'img':
                $this->data['type'] = 'image/png,image/jpg,image/x-png,image/jpeg,image/pjpeg,image/gif,image/bmp,application/octet-stream';
                break;
            case 'excel':
                $this->data['type'] = 'application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                break;
            case 'stream':
                $this->data['type'] = 'application/octet-stream';
                break;
        }
    }

    /**
     * @desc 获取post数据
     * @param *
     * @author leo(suwi.bin)
     * @date 2012-03-23
     */
    private function post($name)
    {
		# 判断是否网络文件
		if(strpos($name, 'http://') !== false)
		{
			$state = false;
			if(strpos($name, '.jpg') !== false)
			{
				$state = true;
				$data['type'] = 'image/jpeg';
			}
			elseif(strpos($name, '.gif') !== false)
			{
				$state = true;
				$data['type'] = 'image/gif';
				$this->_data['filename'] = str_replace('.jpg', '.gif', $this->_data['filename']);
			}
			else
			{
				$data['type'] = '';
			}
			
			if($state == true)
			{
				$data['name'] = md5($name);
				$data['tmp_name'] = $name;
			}
			
			return $data;
		}
		else
		{
			return $this->data['post'][$name] = \Input::get($name);
		}

        return false;
    }

    /**
     * @desc 匹配错误
     * @param *
     * @author leo(suwi.bin)
     * @date 2012-03-23
     */
    private function error($string, $type = 1)
    {
        $errstr = '' ;
        $errstr .= "Upload Error:" . $string . "\n";
        return $errstr;
    }
}