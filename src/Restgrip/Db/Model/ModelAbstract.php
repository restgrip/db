<?php
namespace Restgrip\Db\Model;

use Phalcon\Mvc\Model;

/**
 * @package   Restgrip\Db\Model
 * @author    Sarjono Mukti Aji <me@simukti.net>
 */
abstract class ModelAbstract extends Model
{
    /**
     * Allowed columns to be filterable.
     *
     * @var array
     */
    public static $filterable = [];
    
    /**
     * Allowed columns to be expanded.
     *
     * @var array
     */
    public static $expandable = [];
    
    /**
     * @throws Model\Exception
     */
    public function onValidationFails()
    {
        $errors   = $this->getMessages();
        $messages = [];
        
        foreach ($errors as $key => $message) {
            $messages[$key] = $message->getMessage();
        }
        
        $message = implode(', ', $messages);
        
        throw new Model\Exception($message);
    }
}