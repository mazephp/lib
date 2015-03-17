<?php namespace Maze\Mote;

interface Parse
{
	public function __construct(Compile $compile);

	public function make($param);

	public function get();
}