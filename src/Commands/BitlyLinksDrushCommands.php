<?php

namespace Drupal\bitly_links\Commands;


use Drupal\bitly_links\Exception\InvalidDrushCommandException;
use Drupal\bitly_links\Exception\UnsupportedContentTypeException;
use Drupal\bitly_links\Service\BulkOperationServiceInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Core\Config\ConfigFactoryInterface;

class BitlyLinksDrushCommands extends DrushCommands
{
    private $enabledContentTypes;
    /**
     * @var BulkOperationServiceInterface
     */
    protected $bulkOperationService;
    public function __construct(BulkOperationServiceInterface $bulkOperationService, ConfigFactoryInterface $configFactory)
    {
        $this->bulkOperationService = $bulkOperationService;
        $this->enabledContentTypes = $configFactory->get('bitly_links.settings')->get('enabled_content_types');
    }

    /**
     * Generates bitly links for nodes for the specified content type.
     *
     * @param string $contentType
     *  Content type machine name enabled to support a bitly link. The enabled content type will have a field with machine name bitly_links_field.
     * @param string $baseUrl
     *  The site's base url.
     * @command bitly_links:gen
     * @aliases bitly-gen
     * @options op = update Updates nodes that do not have bitly links associated with them.
     * @options op = all Generates bitly links for all nodes for the specified content type. Nodes with bitly links will
     *  have their bitly links overwritten.
     * @usage drush bitly_links:gen page http://example.com --op=all
     *  Generate bitly links for all page content
     * @usage drush bitly_links:gen page http://example.com --op=update
     *  Updates page content with only missing bitly links.
     * @usage drush bitly_links:gen page --nodes=1,2,3
     *  Generate bitly links for page content with node ids 1,2 and 3 respectively
     */
    public function createBitlyLinks($contentType,  $baseUrl, $options = ['op' => InputOption::VALUE_REQUIRED, 'nodes' => true]){

        $this->checkSupportedContentType($contentType);

        if ($options['op']){
            $operation = $options['op'];
        }
        else{
            $operation = 'nodes';//$options['nodes'];
        }
        switch ($operation){
            case 'all':
                $this->output()->writeln('Generating bitly links for all '.$contentType.' content.');
                $this->bulkOperationService->generate($contentType, 0, $baseUrl);
                break;
            case 'update':
                $this->output()->writeln('Updating '.$contentType.' content with missing bitly links.');
                $this->bulkOperationService->generate($contentType, 1, $baseUrl);
                break;
            case 'nodes':
                $this->output()->writeln('Generating bitly links for nodes with ids '.$options['nodes']);
                $nodes = explode(',', $options['nodes']);

                foreach ($nodes as $node){
                    if (!is_numeric($node)){
                        throw new InvalidDrushCommandException('Supplied Node ID \''.$node.'\' must be a number.');
                    }
                }
                $this->bulkOperationService->generate($contentType, '2', $baseUrl, $nodes);//http://bit.ly/2UMmFEs
                break;
            default:
                throw new InvalidDrushCommandException('Unsupported command option: '.$operation);
        }
    }
    /**
     * Deletes bitly links from nodes. This doesn't however delete them from Bitly Management Platform.
     *
     * @param string $contentType
     *  Content type machine name enabled to support a bitly link. The enabled content type will have a field with machine name bitly_links_field.
     * @command bitly_links:del
     * @aliases bitly-del
     * @options nodes Deletes bitly links for the given nodes.
     * @options all Deletes bitly links for all nodes for the specified content type.
     * @usage drush bitly_links:del page --all
     *  Remove/delete bitly links from all page content
     * @usage drush bitly_links:del page --nodes=1,2,3
     *  Remove/delete bitly links from page content with node ids 1,2 and 3 respectively
     */
    public function deleteBitlyLinks($contentType,  $baseUrl, $options = ['all' => false, 'nodes' => true]){

        $this->checkSupportedContentType($contentType);

        if ($options['op']){
            $operation = $options['op'];
        }
        else{
            $operation = 'nodes';
        }
        switch ($operation){
            case 'all':
                $this->output()->writeln('Delete bitly links for all '.$contentType.' content.');
                $this->bulkOperationService->generate($contentType, '4', $baseUrl);
                break;
            case 'nodes':
                $this->output()->writeln('Generate bitly links for all '.$contentType);
                $nodes = explode(',', $options['nodes']);

                foreach ($nodes as $node){
                    if (!is_numeric($node)){
                        throw new InvalidDrushCommandException('Supplied Node ID \''.$node.'\' must be a number.');
                    }
                }
                $this->bulkOperationService->generate($contentType, '3', $baseUrl, $nodes);
                break;
            default:
                throw new InvalidDrushCommandException('Unsupported command option: '.$operation);
        }
    }
    public function help(){

    }
    private function checkSupportedContentType($contentType){
        $supportedContentTypes = array_keys($this->enabledContentTypes);
        if (!in_array($contentType, $supportedContentTypes)){
            throw  new UnsupportedContentTypeException('The provided content type \''.$contentType.'\' doesn\'t have a bitly links field enabled.');
        }
    }
}