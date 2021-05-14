<?php
declare(strict_types=1);
namespace App\DataBase;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Exception;

final class MultiDbConnectionWrapper extends Connection
{
    public function __construct(
        array $params,
        Driver $driver,
        ?Configuration $config = null,
        ?EventManager $eventManager = null
    ) {
        parent::__construct($params, $driver, $config, $eventManager);
    }
    public function selectDatabase(string $data): void
    {
        if ($this->isConnected()) {
            $this->close();
        }
        $aData = parse_url($data);
        $params = $this->getParams();
        $params['dbname'] = ltrim($aData['path'], '/');
        $params['user'] = $aData['user'];
        $params['password'] = $aData['pass'];
        $params['host'] = $aData['host'];
        $params['port'] = $aData['port'];
        try {
            parent::__construct($params, $this->_driver, $this->_config, $this->_eventManager);
        } catch (Exception $e) {
        }
    }
}