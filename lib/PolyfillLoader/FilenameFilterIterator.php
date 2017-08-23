<?php
/*
 * Based on http://stackoverflow.com/a/3322641/465590
 */
namespace PolyfillLoader;

class FilenameFilterIterator extends \RecursiveRegexIterator
{
	protected $regex;
	public function __construct(\RecursiveIterator $it, $regex)
	{
		$this->regex = $regex;
		parent::__construct($it, $regex);
	}

	// Filter files against the regex
	public function accept()
	{
		return ((!$this->isDot() && !$this->isFile()) || preg_match($this->regex, $this->getFilename()));
	}
}
