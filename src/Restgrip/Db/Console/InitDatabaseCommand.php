<?php
namespace Restgrip\Db\Console;

use Restgrip\Console\Command\CommandAbstract;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package   Restgrip\Db\Console
 * @author    Sarjono Mukti Aji <me@simukti.net>
 */
class InitDatabaseCommand extends CommandAbstract
{
    protected function configure()
    {
        $this->setName('db:init')->setDescription(
            'Create database if not exists (make sure you have permission to create DB using provided app db configs).'
        );
    }
    
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configs      = $this->getDI()->getShared('configs');
        $dbConfig     = $configs->db;
        $driverConfig = $dbConfig->driverConfigs->{$dbConfig->driver};
        
        $pdo = new \PDO(
            sprintf("%s:host=%s;port=%s", $dbConfig->driver, $driverConfig->host, $driverConfig->port),
            $driverConfig->username,
            $driverConfig->password
        );
        
        $schema = $pdo->query(
            sprintf("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '%s'", $driverConfig->dbname)
        );
        
        if ((bool)$schema->fetchColumn() === true) {
            $output->writeln(sprintf("<error>Database '%s' already exists.</error>", $driverConfig->dbname));
            
            return;
        }
        
        $createCommand = sprintf(
            "CREATE DATABASE IF NOT EXISTS %s CHARACTER SET %s COLLATE %s",
            $driverConfig->dbname,
            $driverConfig->charset,
            $driverConfig->collation
        );
        
        $res = $pdo->exec($createCommand);
        
        if ($res != false) {
            $output->writeln(sprintf("<info>Success create database using command: %s</info>", $createCommand));
            
            return;
        }
        
        $output->writeln(sprintf("<error>%s</error>", json_encode($pdo->errorInfo())));
    }
}