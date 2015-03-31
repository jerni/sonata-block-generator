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
            ->addArgument('name', InputArgument::REQUIRED, 'block name?')
            ->addOption('bundle', null, InputOption::VALUE_REQUIRED, 'bundle');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputBunlde = $input->getOption('bundle');
        $bundles = $this->getContainer()->getParameter('kernel.bundles');
        $bundleNames = array_keys($bundles);
        if(in_array($inputBunlde, $bundleNames)){
            $namespace = str_replace('\\'.$inputBunlde, '', $bundles[$inputBunlde]);
            $bundleName = $inputBunlde;
        } else {
            throw new \RuntimeException(
                'The Bundle does not exists.'
            );
        }
        
        $bundleDir = $this->getContainer()->get('kernel')->locateResource('@'.$inputBunlde);
        
        $dir = $this->getContainer()->get('kernel')->getRootDir();
        $bundleBlockDir = $bundleDir.'Block';
        $bundleTwigDir = $bundleDir.'Resources/views/Block';
    
    
        $fs = new Filesystem();

        try {
            if($fs->exists($bundleBlockDir)  == false){
                $fs->mkdir($bundleBlockDir);
            }
            if($fs->exists($bundleTwigDir)  == false){
                $fs->mkdir($bundleTwigDir);
            }
        } catch (IOExceptionInterface $e) {
            $output->writeln("An error occurred while creating your directory at ".$e->getPath());
        }
        
        $twig = str_replace(' ', '_', strtolower($input->getArgument('name')));
        $patternBlock = file_get_contents(dirname(__FILE__).'/Patterns/block.txt');
        $name = ucwords(strtolower($input->getArgument('name')));
        $name = str_replace(' ', '', $name);
        $blockClass = $bundleBlockDir.'/'.$name.'Block.php';
        $fs->touch($blockClass);
        $patternBlock = str_replace(array('{classname}', '{namespace}', '{template}', '{blockdisplayname}'), 
                                    array(
                                          $name, 
                                          $namespace.'\\Block', 
                                          $bundleName.':Block:'.$twig.'.html.twig',
                                          $input->getArgument('name')
                                          ),
                                          $patternBlock);
        $fs->dumpFile($blockClass, $patternBlock);
        
        $patternTwig = file_get_contents(dirname(__FILE__).'/Patterns/twig.txt');
        
        $blockTwig = $bundleTwigDir.'/'.$twig.'.html.twig';
        $fs->touch($blockTwig);
        $fs->dumpFile($blockTwig, $patternTwig);
        
        $blockClassName = $namespace.'\\Block\\'.$name.'Block';
        $patternService = file_get_contents(dirname(__FILE__).'/Patterns/service.txt');
        $servicePattern = str_replace(array('{blockname}', '{blockclass}'), array($twig, $blockClassName), $patternService);

        $output->writeln("\n\n".'Block Service: '."\n\n".$servicePattern);
    }
}