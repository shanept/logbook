<?php
/**
 * The base class for log levels.
 */

namespace Talog;

class Level
{
	protected $level = '';

	public function __construct() {}

	public function get_level()
	{
		return $this->level;
	}
}
