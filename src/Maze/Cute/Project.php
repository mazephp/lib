<?php namespace Maze\Cute;

class Project
{
	/**
	 * read file
	 *
	 * @var string
	 */
	const file = 'data/project.php';

	/**
	 * content
	 *
	 * @var array
	 */
	static protected $content;
	
	/**
	 * register project file
	 *
	 * @return mixed
	 */
	static public function register()
	{
		$file = MAZE_PATH . self::file;

		self::$content = array();

		if(is_file($file))
		{
			require $file;

			self::$content = $project;
		}

		if(empty(self::$content[MAZE_PROJECT_NAME]))
		{
			self::$content[MAZE_PROJECT_NAME] = array('path' => str_replace(MAZE_PATH, '', MAZE_PROJECT_PATH), 'lang' => MAZE_PROJECT_NAME);

			file_put_contents($file, '<?php $project = ' . var_export(self::$content, true) . ';');
		}
	}

	/**
	 * update project file
	 *
	 * @return mixed
	 */
	static public function update($key, $index, $value)
	{
		$file = MAZE_PATH . self::file;

		self::$content = array();

		if(is_file($file))
		{
			require $file;

			self::$content = $project;

			if(isset(self::$content[$key]))
			{
				self::$content[$key][$index] = $value;

				file_put_contents($file, '<?php $project = ' . var_export(self::$content, true) . ';');
			}
		}
	}

	/**
	 * read
	 *
	 * @return mixed
	 */
	static public function read()
	{
		return self::$content;
	}
}