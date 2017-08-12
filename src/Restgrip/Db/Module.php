<?php
namespace Restgrip\Db;

use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Db\Adapter\Pdo\Postgresql;
use Phalcon\Db\Adapter\Pdo\Sqlite;
use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Mvc\Model\Transaction\Manager as TransactionManager;
use Phalcon\Mvc\Model\Metadata\Redis as RedisMetaData;
use Phalcon\Mvc\Model\Metadata\Memory as MemoryMetaData;
use Phalcon\Mvc\Model\Metadata\Files as FilesMetaData;
use Phalcon\Mvc\Model\Metadata\Apc as ApcMetaData;
use Phalcon\Mvc\Model\Metadata\Memcache as MemcacheMetaData;
use Phalcon\Mvc\Model\Metadata\Libmemcached as LibmemcachedMetaData;
use Restgrip\Db\Console\InitDatabaseCommand;
use Restgrip\Db\Console\ResetMetadataCommand;
use Restgrip\Module\ModuleAbstract;

/**
 * @package   Restgrip\Db
 * @author    Sarjono Mukti Aji <me@simukti.net>
 */
class Module extends ModuleAbstract
{
    /**
     * @var array
     */
    protected $defaultConfigs = [
        'driver'          => 'mysql',
        'metadata'        => 'memory',
        'driverConfigs'   => [
            'mysql'  => [
                'host'      => '127.0.0.1',
                'username'  => 'username',
                'password'  => 'password',
                'dbname'    => 'database',
                'port'      => 3306,
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'options'   => [],
            ],
            'pgsql'  => [
                'host'      => '127.0.0.1',
                'username'  => 'username',
                'password'  => 'password',
                'dbname'    => 'database',
                'port'      => 3306,
                'charset'   => 'utf8',
                'collation' => 'en_GB.UTF8',
                'prefix'    => '',
                'options'   => [],
            ],
            'sqlite' => [
                'dbname' => '/path/to/your_db.sqlite',
            ],
        ],
        // metadata storage
        'metadataConfigs' => [
            'redis'        => [
                'host'       => '127.0.0.1',
                'port'       => 6379,
                'db'         => 0,
                'statsKey'   => '_DB_METADATA_',
                'persistent' => 0,
                'lifetime'   => 2592000,
                'index'      => 3,
            ],
            'files'        => [
                // dir with ending '/'
                'metaDataDir' => null,
            ],
            'apc'          => [
                'prefix'   => 'metadata-prefix',
                'lifetime' => 2592000,
            ],
            'memcache'     => [
                'prefix'     => 'metadata-prefix',
                'lifetime'   => 2592000,
                'host'       => '127.0.0.1',
                'port'       => 11211,
                'persistent' => false,
            ],
            'libmemcached' => [
                'servers'  => [
                    [
                        'host'   => '127.0.0.1',
                        'port'   => 11211,
                        'weight' => 1,
                    ],
                ],
                'client'   => [
                    // \Memcached::OPT_HASH       => \Memcached::HASH_MD5,
                    // \Memcached::OPT_PREFIX_KEY => 'restgrip.',
                ],
                'lifetime' => 2592000,
                'prefix'   => 'restgrip_',
            ],
        ],
    ];
    
    protected function services()
    {
        $app      = $this->app;
        $dbConfig = new Config($this->defaultConfigs);
        $configs  = $this->getDI()->getShared('configs');
        if ($configs->get('db') instanceof Config) {
            $dbConfig->merge($configs->db);
        }
        $configs->offsetSet('db', $dbConfig);
        
        $this->getDI()->setShared(
            'db',
            function () use ($app, $configs) {
                $config   = $configs->db;
                $driver   = $config->driver;
                $instance = null;
                
                switch ($driver) {
                    case 'mysql':
                        $instance = new Mysql($config->driverConfigs->{$driver}->toArray());
                        $instance->setEventsManager($app->getEventsManager());
                        break;
                    case 'pgsql':
                        $instance = new Postgresql($config->driverConfigs->{$driver}->toArray());
                        $instance->setEventsManager($app->getEventsManager());
                        break;
                    case 'sqlite':
                        $instance = new Sqlite($config->driverConfigs->{$driver}->toArray());
                        $instance->setEventsManager($app->getEventsManager());
                        break;
                }
                
                return $instance;
            }
        );
        
        $this->getDI()->setShared(
            'transactionManager',
            function () use ($app) {
                $instance = new TransactionManager();
                $instance->setDbService('db');
                
                return $instance;
            }
        );
        
        $this->getDI()->setShared(
            'modelsManager',
            function () use ($app) {
                $instance = new ModelsManager();
                $instance->setEventsManager($app->getEventsManager());
                
                return $instance;
            }
        );
        
        $this->getDI()->setShared(
            'modelsMetadata',
            function () use ($app, $configs) {
                $config   = $configs->db;
                $metadata = $config->metadata;
                $instance = null;
                
                switch ($metadata) {
                    case 'redis':
                        $config   = $config->metadataConfigs->{$metadata}->toArray();
                        $instance = new RedisMetaData($config);
                        break;
                    case 'memory':
                        // memory adapter does not have any config to set
                        // @link https://github.com/phalcon/cphalcon/blob/2d3aa171588d2d82ed26ade076c70d77ca07e2a3/phalcon/mvc/model/metadata/memory.zep#L42-L43
                        $instance = new MemoryMetaData();
                        break;
                    case 'files':
                        $config = $config->metadataConfigs->{$metadata}->toArray();
                        if ($configs['metaDataDir'] == null) {
                            $configs['metaDataDir'] = sys_get_temp_dir().DIRECTORY_SEPARATOR;
                        }
                        $instance = new FilesMetaData($config);
                        break;
                    case 'apc':
                        $config   = $config->metadataConfigs->{$metadata}->toArray();
                        $instance = new ApcMetaData($config);
                        break;
                    case 'memcache':
                        $config   = $config->metadataConfigs->{$metadata}->toArray();
                        $instance = new MemcacheMetaData($config);
                        break;
                    case 'libmemcached':
                        $config   = $config->metadataConfigs->{$metadata}->toArray();
                        $instance = new LibmemcachedMetaData($config);
                        break;
                }
                
                if (!$instance) {
                    throw new \InvalidArgumentException(sprintf("Invalid metadata cache driver '%s'", $metadata));
                }
                
                return $instance;
            }
        );
    }
    
    protected function console()
    {
        $this->getDI()->getShared('console')->addCommands(
            [
                new InitDatabaseCommand($this->getDI()),
                new ResetMetadataCommand($this->getDI()),
            ]
        );
    }
}