<?php
// +----------------------------------------------------------------------
// | PHP [ JUST YOU ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2017 http://www.jyphp.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Albert <albert_p@foxmail.com>
// +----------------------------------------------------------------------
namespace JYPHP\Core\Console;

use JYPHP\Core\Application;
use JYPHP\Core\Interfaces\Application\IApplication;
use \Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends SymfonyCommand
{
    /**
     * 命令描述
     * @var string
     */
    protected $desc = "";

    /**
     * 命令名
     * @var string
     */
    protected $name = "";

    /**
     * @var Output
     */
    protected $output;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Input
     */
    protected $input;

    public function __construct($name = null, IApplication $application)
    {
        parent::__construct($name);
        $this->app = $application;
    }

    public function configure()
    {
        $this->setDescription($this->desc);
        $this->setName($this->name);
        parent::configure(); // TODO: Change the autogenerated stub
    }

    public function handle()
    {

    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
        $methods      = method_exists($this, "handle") ? "handle" : "fire";
        $this->app->call([$this, $methods]);
    }
}