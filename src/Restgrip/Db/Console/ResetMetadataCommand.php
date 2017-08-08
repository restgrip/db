<?php
namespace Restgrip\Db\Console;

use Restgrip\Console\Command\CommandAbstract;
use Restgrip\Db\Service\DbServiceTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package   Restgrip\Db\Console
 * @author    Sarjono Mukti Aji <me@simukti.net>
 */
class ResetMetadataCommand extends CommandAbstract
{
    use DbServiceTrait;
    
    protected function configure()
    {
        $this->setName('db:reset-metadata')->setDescription('Reset/clear DB models metadata cache.');
    }
    
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getMetadataManager()->reset();
        $this->getModelsManager()->clearReusableObjects();
        $output->writeln('<info>Metadata cleared</info>');
    }
}