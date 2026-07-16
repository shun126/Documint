<?php

////////////////////////////////////////////////////////////////////////////////
/*
Page information class.
*/
class PageInfomation
{
	private $title;
	private $networkPath;
	private $filePath;
	private $outputFilePath;
	private $categories;

	public function __construct($title, $networkPath, $filePath, $outputFilePath, $categories)
	{
		$this->title = $title;
		$this->networkPath = $networkPath;
		$this->filePath = $filePath;
		$this->outputFilePath = $outputFilePath;
		$this->categories = $categories;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function getNetworkPath()
	{
		return $this->networkPath;
	}

	public function getFilePath()
	{
		return $this->filePath;
	}

	public function getOutputFilePath()
	{
		return $this->outputFilePath;
	}

	public function getCategories()
	{
		return $this->categories;
	}
};
