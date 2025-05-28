<?php
declare(strict_types=1);

namespace App\Service;

use App\Adapter\Interface\IAmoCrmAuth;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class AuthService
{
    public function __construct(
        private ParameterBagInterface $params,
        private IAmoCrmAuth $authAdapter,
        private HttpClientInterface $httpClient,
        private CacheItemPoolInterface $cache,
        private ParameterBagInterface $param
    ) {

        $this->httpClient = HttpClient::create();
        $this->params = $params;

        $redisConnection = RedisAdapter::createConnection($_ENV['REDIS_DSN']);
        $this->cache = new RedisAdapter($redisConnection);

    }
    
    public function getAccessToken(): string {
        
        $token = $this->cache->getItem('amocrm_access_token');
        
        if (!$token->isHit()) {
            $this->refreshTokens();
            $token = $this->cache->getItem('amocrm_access_token');
        }

        return $token->get();

    }

    private function refreshTokens(): void {

        $refreshToken = $this->cache->getItem('amocrm_refresh_token')->get();
        
        if (empty($refreshToken)) {
            $this->getInitialTokens();
            return;
        }

        $response = $this->authAdapter->getTokensByRefreshToken($refreshToken);

        if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
            $this->storeTokens($response->toArray());    
        } else {
            throw new amoCrmApiException(json_encode($content, JSON_UNESCAPED_UNICODE));
        }
    }

    private function getInitialTokens(): void
    {
        $response = $this->authAdapter->getTokensByAuthorizationCode();
        
        if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
            $this->storeTokens($response->toArray());    
        } else {
            throw new amoCrmApiException(json_encode($content, JSON_UNESCAPED_UNICODE));
        } 
    }

    private function storeTokens(array $tokens): void
    {
        $accessToken = $this->cache->getItem('amocrm_access_token');
        $accessToken->set($tokens['access_token']);
        $accessToken->expiresAfter($tokens['expires_in'] - 60);
        $this->cache->save($accessToken);

        $refreshToken = $this->cache->getItem('amocrm_refresh_token');
        $refreshToken->set($tokens['refresh_token']);
        $this->cache->save($refreshToken);
    }

    public function login() {

        $this->getInitialTokens();
    }
}