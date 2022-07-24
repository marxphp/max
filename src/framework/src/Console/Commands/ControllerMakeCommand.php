<?php

declare(strict_types=1);

/**
 * This file is part of MaxPHP.
 *
 * @link     https://github.com/marxphp
 * @license  https://github.com/marxphp/max/blob/master/LICENSE
 */

namespace Max\Framework\Console\Commands;

use Max\Utils\Exceptions\FileNotFoundException;
use Max\Utils\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ControllerMakeCommand extends Command
{
    protected string $stubsPath = __DIR__ . '/stubs/';

    protected function configure()
    {
        $this->setName('make:controller')
            ->setDescription('Making controllers.')
            ->setDefinition([
                new InputArgument('controller', InputArgument::REQUIRED, 'A controller name such as `user`.'),
                new InputOption('rest', 'r', InputOption::VALUE_OPTIONAL, 'Make a restful controller.'),
            ]);
    }

    /**
     * @throws FileNotFoundException
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystem               = new Filesystem();
        $controller               = $input->getArgument('controller');
        $stubFile                 = $this->stubsPath . ($input->hasOption('rest') ? 'controller_rest.stub' : 'controller.stub');
        [$namespace, $controller] = $this->parse($controller);
        $controllerPath           = base_path('app/Http/Controllers/' . str_replace('\\', '/', $namespace) . '/');
        $controllerFile           = $controllerPath . $controller . 'Controller.php';
        if ($filesystem->exists($controllerFile)) {
            $output->writeln('<comment>[WARN]</comment> 控制器已经存在!');
            return 1;
        }
        $filesystem->exists($controllerPath) || $filesystem->makeDirectory($controllerPath, 0777, true);
        $filesystem->put($controllerFile, str_replace(['{{namespace}}', '{{class}}', '{{path}}'], ['App\\Http\\Controllers' . $namespace, $controller . 'Controller', strtolower($controller)], $filesystem->get($stubFile)));
        $output->writeln("<info>[INFO]</info> 控制器App\\Http\\Controllers{$namespace}\\{$controller}Controller创建成功！");

        return 1;
    }

    /**
     * @param $input
     */
    protected function parse($input): array
    {
        $array     = explode('/', $input);
        $class     = ucfirst(array_pop($array));
        $namespace = implode('\\', array_map(fn ($value) => ucfirst($value), $array));
        if (! empty($namespace)) {
            $namespace = '\\' . $namespace;
        }
        return [$namespace, $class];
    }
}
