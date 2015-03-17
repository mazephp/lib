<?php namespace Maze\Accel;

interface Store
{
	public function register();
	public function one();
	public function all();
	public function set();
	public function del();
}