<?php

namespace Lib\Response;

class Download
{
    /**
     * @var string
     */
    public $download_file;
    public function __construct($file)
    {
        $this->download_file = $file;
    }
}
