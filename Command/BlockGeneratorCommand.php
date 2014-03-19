<?php

namespace Jse\BlockGeneratorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class BlockGeneratorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('create:block')
            ->setDescription('Create Block Service')
            ->addArgument('name', InputArgument::OPTIONAL, 'block name?')
            ->addOption('bundle', null, InputOption::VALUE_REQUIRED, 'bundle');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = $this->getContainer()->get('kernel')->getRootDir();
        $bundleBlockDir = $dir.'/../src/'.$input->getOption('bundle').'/Block';
        $bundleTwigDir = $dir.'/../src/'.$input->getOption('bundle').'/Resources/views/Block';
    
        $fs = new Filesystem();

        try {
            if($fs->exists($bundleBlockDir)  == false){
                $fs->mkdir($bundleBlockDir);
            }
            if($fs->exists($bundleTwigDir)  == false){
                $fs->mkdir($bundleTwigDir);
            }
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while creating your directory at ".$e->getPath();
        }
        
        $twig = strtolower($input->getArgument('name'));
        $patternBlock = file_get_contents(dirname(__FILE__).'/Patterns/block.txt');
        $name = ucfirst(strtolower($input->getArgument('name')));
        $blockClass = $bundleBlockDir.'/'.$name.'Block.php';
        $fs->touch($blockClass);
        $patternBlock = str_replace(array('{classname}', '{namespace}', '{template}'), 
                                    array(
                                          $name, 
                                          str_replace('/', '\\', $input->getOption('bundle')).'\\Block', 
                                          str_replace('/', '', $input->getOption('bundle')).':Block:'.$twig.'.html.twig'
                                          ),
                                          $patternBlock);
        $fs->dumpFile($blockClass, $patternBlock);
        
        $patternTwig = file_get_contents(dirname(__FILE__).'/Patterns/twig.txt');
        
        $blockTwig = $bundleTwigDir.'/'.$twig.'.html.twig';
        $fs->touch($blockTwig);
        $fs->dumpFile($blockTwig, $patternTwig);
        
        $bundleServiceDir = $dir.'/../src/'.$input->getOption('bundle').'/Resources/config/services.xml';
        $serviceContent = file_get_contents($bundleServiceDir);
        $blockClassName = str_replace('/', '\\', $input->getOption('bundle')).'\\Block\\'.$name.'Block';
        $updateService = trim(str_replace(array('</services>', '</container>'), array('', ''), $serviceContent),"\r\n");
        $patternService = file_get_contents(dirname(__FILE__).'/Patterns/service.txt');
        $servicePattern = str_replace(array('{blockname}', '{blockclass}'), array($twig, $blockClassName), $patternService);
        $updateService = trim(str_replace($servicePattern, '', $updateService),"\r\n");
        $fs->dumpFile($bundleServiceDir, $updateService.$servicePattern."\n\t\t".'</services>'."\n".'</container>');

        $output->writeln($twig.'.block.service');
    }
}