<?php

namespace Drupal\bitly_links\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Language\LanguageManagerInterface;

class BulkOperationManager implements BulkOperationServiceInterface
{
    /**
     * @var \Drupal\Core\Language\LanguageManagerInterface;
     */
    protected $languageManager;
    /**
     * @var \Drupal\Core\Path\AliasManager
     */
    protected $aliasManager;
    /**
     * @var \Drupal\Core\Routing\RequestContext
     */
    protected $requestContext;
    /**
     * @var \Drupal\bitly_links\Service\BitlyLinksManager;
     */
    protected $bitlyLinksService;
    /**
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;
    public function __construct(BitlyLinksServiceInterface $bitlyLinksService, LanguageManagerInterface $languageManager,
                                AliasManager $aliasManager, RequestContext $requestContext, EntityTypeManagerInterface $entityTypeManager){
        $this->languageManager = $languageManager;
        $this->aliasManager = $aliasManager;
        $this->requestContext = $requestContext;
        $this->bitlyLinksService = $bitlyLinksService;
        $this->entityTypeManager = $entityTypeManager;
    }
    private function getPathAlias($node){
        $systemPath = '/node/'.$node->id();
        $langCode = $this->languageManager->getCurrentLanguage()->getId();
        $alias = $this->aliasManager->getAliasByPath($systemPath, $langCode);
        if (!isset($alias)){
            return $systemPath;
        }
        return $alias;
    }
    /**
     * @inheritdoc
     */
    public function generate($contentType, $operation, $baseUrl = NULL, $nodes = array()){
        $status = 'Operation '.$operation.' failed';
        $storage = $this->entityTypeManager->getStorage('node');
        if (!isset($baseUrl)){
            $baseUrl = $this->requestContext->getCompleteBaseUrl();
        }
        //$baseUrl = $this->requestContext->getCompleteBaseUrl();

        if (empty($nodes)){
            $query = $storage->getQuery()->condition('type', $contentType);
            $ids = $query->execute();
            $nodes = $storage->loadMultiple($ids);

            foreach ($nodes as $node){
                $aliasPath = $this->getPathAlias($node);

                switch($operation){
                    case 0:
                        $response = $this->bitlyLinksService->shorten($baseUrl.$aliasPath);
                        $bityLink = json_decode($response)->link;
                        $node->set('bitly_links_field', $bityLink);
                        $node->save();
                        $status = 'Operation '.$operation.' completed.';
                        break;
                    case 1:
                        $response = $this->bitlyLinksService->shorten($baseUrl.$aliasPath);
                        $bityLink = json_decode($response)->link;
                        //$bitlyLink = $node->get('bitly_links_field')->value;
                        $hasBitlyLink = $node->get('bitly_links_field')->isEmpty();
                        if($hasBitlyLink){
                            $node->set('bitly_links_field', $bityLink);
                            $node->save();
                        }
                        $status = 'Operation '.$operation.' completed.';
                        break;
                    case 4:
                        $node->set('bitly_links_field', '');
                        $node->save();
                        $status = 'Operation '.$operation.' completed.';
                        break;
                }
            }

            return $status;
        }
        else{ //handles specific nodes
            $nodes = $storage->loadMultiple($nodes);
            foreach ($nodes as $node){
                $aliasPath = $this->getPathAlias($node);

                switch ($operation){
                    case 2:
                        $response = $this->bitlyLinksService->shorten($baseUrl.$aliasPath);
                        $bityLink = json_decode($response)->link;
                        $node->set('bitly_links_field', $bityLink);
                        $node->save();
                        $status = 'Operation '.$operation.' completed.';
                        break;
                    case 3:
                        $node->set('bitly_links_field', '');
                        $node->save();
                        $status = 'Operation '.$operation.' completed.';
                        break;
                }
            }
            return $status;
        }
    }
}