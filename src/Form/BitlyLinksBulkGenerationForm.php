<?php

namespace Drupal\bitly_links\Form;

use Drupal\bitly_links\Service\BitlyLinksManager;
use Drupal\bitly_links\Service\BulkOperationServiceInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class BitlyLinksBulkGenerationForm extends FormBase
{
    /**
     * @var \Drupal\Core\Config\ConfigFactoryInterface;
     */
    protected $config;
    /**
     * @var BulkOperationServiceInterface Z
     */
    protected $bulkOperationService;

    public function __construct(ConfigFactoryInterface $configFactory, BulkOperationServiceInterface $bulkOperationService)
    {
        $this->config = $configFactory->get('bitly_links.settings');
        $this->bulkOperationService = $bulkOperationService;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('config.factory'),
            $container->get('bitly_links.bulk_operations')
        );
    }
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'bitly_links_bulk_generation_form';
    }
    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state){
        $enableContentTypes = $this->config->get('enabled_content_types');

        $form['bulk_info'] = array(
            '#type' => 'markup',
            '#markup' => '<div>This bulk operation only generates bitly links for content that don\'t have bitly links associated with them. 
Only content types that have been enabled to support bitly links will be made available for bulk operations.</div>'
        );
        $form['enabled_types'] = array(
            '#type' => 'radios',
            '#title' => $this->t('Select enabled content types for which to generate bitly links.'),
            '#options' => $enableContentTypes,
            '#description' => $this->t('Content types with the bitly_link field enabled.'),
            '#prefix' => '<div id="enabled_types">',
            '#suffix' => '</div>',
            '#ajax' => array(
                'callback' => '::resetWarnings',
                'event' => 'change',
            ),
        );

        $form['bulk_operation'] = array(
            '#type' => 'radios',
            '#title' => $this->t('Select bulk operation'),
            '#options' => array(
                0 => $this->t('Generate bitly links for selected content type. Any available bitly links will be deleted.'),
                1 => $this->t('Generate missing bitly links for selected content type. Only content without bitly links will be updated.'),
                2 => $this->t('Generate bitly links for specific content. Any available bitly link will be deleted.'),
                3 => $this->t('Delete bitly link for specific content.'),
                4 => $this->t('Delete all bitly links for the selected content type.'),
            ),
            '#default_value' => 0,
            '#ajax' => array(
                'callback' => '::bulkOperation',
                'event' => 'change',
            ),
        );
        $form['specific_nodes'] = array(
            '#type' => 'textarea',
            '#description' => $this->t('Enter node id or for multiple nodes enter IDs separated by comma.'),
            /*'#attributes' => array(
                'class' => array('specific_nodes')
            ),*/
            '#prefix' => '<div class="nodes">',
            '#suffix' => '</div>',
        );
        $form['specific_nodest'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="specific_nodes"></div>',
        );
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Run'),
            '#button_type' => 'primary',
            '#ajax' => array(
                'callback' => '::runOperation',
                'event' => 'click',
                'progress' => array(
                    'type' => 'throbber',
                    'message' => 'Running bulk operation',
                ),
            ),
        );
        $form['#attached']['library'][] = 'bitly_links/bulk_operations';
        return $form;
    }
    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state){

    }
    public function bulkOperation(array &$form, FormStateInterface $form_state){
        $operation = $form_state->getValue('bulk_operation');
        $ajaxResponse = new AjaxResponse();
        $warningCss = array(
            'border-color' => '',
            'border-style' => '',
        );
        $ajaxResponse->addCommand(new CssCommand('#edit-specific-nodes--description', ['color' => '']));
        $ajaxResponse->addCommand(new CssCommand('.nodes', $warningCss));

        switch ($operation){
            case 0:
                //Generate bitly links for all selected enabled content type. Any available bitly links will be deleted
                $ajaxResponse->addCommand(new CssCommand('.nodes', ['display' => 'none']));
                break;
            case 1:
                //Generate missing bitly links for selected enabled content type
                $ajaxResponse->addCommand(new CssCommand('.nodes', ['display' => 'none']));
                break;
            case 2:
                //Generate bitly link for a single or specific content. Any available bitly link will be deleted
                $ajaxResponse->addCommand(new CssCommand('.nodes', ['display' => 'block']));
                break;
            case 3:
                //Delete bitly link for single or specific content
                $ajaxResponse->addCommand(new CssCommand('.nodes', ['display' => 'block']));
                break;
            case 4:
                //Delete bitly links for all selected enabled content type
                $ajaxResponse->addCommand(new CssCommand('.nodes', ['display' => 'none']));
                break;
        }
        return $ajaxResponse;
    }
    public function runOperation(array &$form, FormStateInterface $form_state){
        $baseUrl = NULL;
        $ajaxResponse = new AjaxResponse();
        $specificOperations = array(2, 3);
        $contentType = $form_state->getValue('enabled_types');
        $operation = $form_state->getValue('bulk_operation');
        $warningCss = array(
            'border-color' => 'red',
            'border-style' => 'solid',
        );

        if (!isset($contentType)){

            $ajaxResponse->addCommand(new CssCommand('#enabled_types', $warningCss));
            $ajaxResponse->addCommand(new HtmlCommand('.specific_nodes', 'Select Content type'));
            $ajaxResponse->addCommand(new CssCommand('.specific_nodes', ['color' => 'red']));
            return $ajaxResponse;
        }

        if (in_array($operation, $specificOperations)){
            $nodes = $form_state->getValue('specific_nodes');
            if(!isset($nodes) || trim($nodes) == ''){
                $ajaxResponse->addCommand(new CssCommand('#edit-specific-nodes--description', ['color' => 'red']));
                $ajaxResponse->addCommand(new CssCommand('.nodes', $warningCss));
                return $ajaxResponse;
            }
            else{
                $nodes = explode(',', $nodes);
                $status = $this->bulkOperationService->generate($contentType, $operation, $baseUrl, $nodes);
            }
        }
        else{
            $status = $this->bulkOperationService->generate($contentType, $operation, $baseUrl);
        }

        $ajaxResponse->addCommand(new HtmlCommand('.specific_nodes', $status));

        return $ajaxResponse;
    }
    public function resetWarnings(array &$form, FormStateInterface $form_state){
        $ajaxResponse = new AjaxResponse();

        $warningCss = array(
            'border-color' => '',
            'border-style' => '',
        );
        $ajaxResponse->addCommand(new CssCommand('#enabled_types', $warningCss));
        $ajaxResponse->addCommand(new HtmlCommand('.specific_nodes', ''));
        $ajaxResponse->addCommand(new CssCommand('.specific_nodes', ['color' => '']));

        return $ajaxResponse;
    }
}