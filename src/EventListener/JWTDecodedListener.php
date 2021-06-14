<?php

namespace App\EventListener;

use App\DataBase\MultiDbConnectionWrapper;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;

class JWTDecodedListener
{
    private string $key;

    private EntityManagerInterface $em;

    public function __construct($key, EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->key = $key;
    }

    public function onJWTDecoded(JWTDecodedEvent $event)
    {
        $payload = $event->getPayload();
        $cipher = 'AES-128-CBC';
        $url = openssl_decrypt(
            base64_decode($payload['base_url']),
            $cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            base64_decode($payload['base_url_iv'])
        );
        $connection = $this->em->getConnection();
        if (!$connection instanceof MultiDbConnectionWrapper) {
            throw new \RuntimeException('Wrong connection');
        }
        $connection->selectDatabase($url);
    }
}
