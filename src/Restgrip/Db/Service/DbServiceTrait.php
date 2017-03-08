<?php
namespace Restgrip\Db\Service;

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\DiInterface;
use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Mvc\Model\Metadata\Redis as MetaData;
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;

/**
 * @method DiInterface getDI()
 * @package   Restgrip\Db\Service
 * @author    Sarjono Mukti Aji <me@simukti.net>
 */
trait DbServiceTrait
{
    /**
     * @return Mysql
     */
    public function getDb()
    {
        return $this->getDI()->getShared('db');
    }
    
    /**
     * @return TransactionManager
     */
    public function getDbTransaction()
    {
        return $this->getDI()->getShared('transactionManager');
    }
    
    /**
     * @return ModelsManager
     */
    public function getModelsManager()
    {
        return $this->getDI()->getShared('modelsManager');
    }
    
    /**
     * @return MetaData
     */
    public function getMetadataManager()
    {
        return $this->getDI()->getShared('modelsMetadata');
    }
}