<?php

namespace Drupal\bitly_links\Controller;

use Drupal\bitly_links\Service\BitlyLinksServiceInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\State\State;

class BitlyLinksController extends ControllerBase
{
    protected $bitlyLinksService;
    protected $configFactory;
    protected $state;

    public function __construct(BitlyLinksServiceInterface $bitlyLinksService, State $state)
    {
        $this->bitlyLinksService = $bitlyLinksService;
        $this->state = $state;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('bitly_links.manager_service'),
            $container->get('state')
        );
    }
    public function getAuthorization(){
        $baseUrl = $this->state->get('bitly_links_base_url');
        $clientId = $this->state->get('bitly_links_client_id');
        $redirectUri = $this->state->get('bitly_links_redirect_uri');

        $url = $baseUrl."?client_id=".$clientId."&redirect_uri=".$redirectUri;
        return array(
            '#type' => 'markup',
            '#markup' => '<a href='.$url.' target="_blank">Authorize App</a>',
        );
    }
    public function oauthPage(Request $request){
        $code = $request->query->get('code');
        $accessToken = $this->bitlyLinksService->getAccessToken($code);
        $params = array();
        parse_str($accessToken, $params);
        $this->state->set('bitly_links_access_token',$params['access_token']);

        return $this->redirect('bitly_links.access_status');
    }
    public function accessStatus(){

        return array(
            '#type' => 'markup',
            '#markup' => '<div>The app has been successfully authorized.</div>',
        );
    }
    public function testShort(){
        $shortened = $this->bitlyLinksService->shorten('http://www.cnn.com');

        $output = json_decode($shortened);


        return new JsonResponse($output);
    }
    public function adminOverview(){
        $build['bitly_links_settings_form'] = $this->formBuilder()->getForm('Drupal\bitly_links\Form\BitlyLinksNodeSettingsForm');
        //$build['bitly_links_auth_form'] = $this->formBuilder()->getForm('Drupal\bitly_links\Form\BitlyLinksAuthorizationForm');

        return $build;
    }
}