<?php

namespace Drupal\bitly_links\Service;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\State\State;

class BitlyLinksManager implements BitlyLinksServiceInterface
{
    //for purposes of backward compatibility, visibility (private) will be removed in beta releases.
    const BASEURL = "https://api-ssl.bitly.com";
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $accessToken;
    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;
    /**
     * @var \Drupal\Core\Config\ConfigFactory
     */
    protected $config;
    /**
     * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
     */
    protected $logger;
    protected $state;

    public function __construct(Client $client, LoggerChannelFactoryInterface $logger, State $state)
    {
        $this->client = $client;
        $this->logger = $logger->get('bitly_links');
        $this->clientId = $state->get('bitly_links_client_id');
        $this->clientSecret = $state->get('bitly_links_client_secret');
        $this->redirectUri = $state->get('bitly_links_redirect_uri');
        $this->state = $state;
    }

    public function getAccessToken($code){
        $url = self::BASEURL.'/oauth/access_token?client_id='.$this->clientId."&redirect_uri=".$this->redirectUri."&code=".$code."&client_secret=".$this->clientSecret;

        try{
            $response = $this->client->post($url, [
                'headers' => [
                    "Content-Type" => "application/json"
                ]
            ]);
            return $response->getBody()->getContents();
        }
        catch(RequestException $e){
            $this->logger->error('Get Access token exception: '.$e->getMessage());
            return FALSE;
        }
    }
    public function shorten($longUrl){

        $url = self::BASEURL.'/v4/shorten';
        $accessToken = $this->state->get('bitly_links_access_token');
        $bitly = new \stdClass();
        $bitly->long_url = $longUrl;

        $response = $this->client->post($url, [
            'body' => json_encode($bitly),
            'headers' => [
                'Authorization' => 'Bearer ' .$accessToken,
                'Content-Type' => 'application/json'
            ]
        ]);

        return $response->getBody()->getContents();
    }
}