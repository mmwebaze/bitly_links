<?php

namespace Drupal\bitly_links\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

class BitlyLinksSettingsForm extends ConfigFormBase
{
    /**
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;
    /**
     * @var \Drupal\Core\Entity\EntityFieldManagerInterface
     */
    protected $entityFieldManager;
    public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager)
    {
        parent::__construct($configFactory);
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFieldManager = $entityFieldManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('config.factory'),
            $container->get('entity_type.manager'),
            $container->get('entity_field.manager')
        );
    }
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'bitly_links_settings_form';
    }
    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return ['bitly_links.settings'];
    }
    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('bitly_links.settings');
        $form['enabled_content_types'] = [
            '#type' => 'details',
            '#open' => TRUE,
            '#title' => $this->t('Enabled content types'),
            '#description' => $this->t('Enable to add a bitly path field. A field called bitly_links_field will be added to the content type selected if it doesn\'t exist.'),
            '#tree' => TRUE,
        ];

        $storage = $this->entityTypeManager->getStorage('node_type');
        $types = $storage->loadMultiple();

        foreach ($types as $type => $contentType){
            $form['enabled_content_types'][$type] = array(
                '#type' => 'checkbox',
                '#title' => $contentType->label(),
            );
        }

        return parent::buildForm($form, $form_state);
    }
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        foreach ($form_state->getValues() as $key => $value) {
            //print_r($key." -- ".$value."\n");
            if ($key == 'enabled_content_types'){
                foreach ($value as $type => $enabled){

                    if ($enabled == '1'){
                        $entity = $this->checkFieldExists('bitly_links_field', $type);
                        print_r($entity);die();
                        $this->createField('bitly_links_field', 'bitly links', $type, 'node');
                    }
                }
            }
        }
        //die();
        parent::submitForm($form, $form_state);
    }
    private function createField($fieldName, $fieldLabel, $bundle, $entityType){

        if ($this->checkEntityStorage() == 0){
            FieldStorageConfig::create(
                array(
                    'field_name' => $fieldName,
                    'entity_type' => $entityType,
                    'type' => 'string',
                    'settings' => [
                        'max_length' => '255',
                    ],
                    'cardinality' => 1,
                )
            )->save();
        }

        FieldConfig::create([
            'field_name' => $fieldName,
            'entity_type' => $entityType,
            'bundle' => $bundle,
            'label' => $fieldLabel,
            'field_type' => 'string',
            'widget' => [

            ]
        ])->save();
        /*$storage = \Drupal::entityTypeManager()->getStorage('entity_form_display');
        $entity_form_display = $storage->load('node.page.default');*/

        $entity_form_display = EntityFormDisplay::load($entityType.'.'.$bundle.'.default');
        //print_r($entity_form_display->getComponents()['bitly_links_field']);die();
        $entity_form_display->setComponent($fieldName, [
            'type' => 'string_textfield',
            'weight' => 8,
            'region' => 'content',
            'third_party_settings' => [],
            'settings' => [
                'size' => 60,
                'placeholder' => ''
            ]
        ]);
        $entity_form_display->save();
        //print_r($entity_form_display->getComponent('bitly_links_field'));die();
        //$options = $entity_form_display->getComponent($fieldName);


    }
    private function checkEntityStorage(){
        $storage = \Drupal::entityTypeManager()->getStorage('field_storage_config');
        $type = $storage->load('node.bitly_links_field');

        return count($type);
    }
    private function checkFieldExists($fieldId, $contentType){
        $fields = $this->entityFieldManager->getFieldDefinitions('node', $contentType);
        foreach ($fields as $field_name => $field_definition) {
            if (!empty($field_definition->getTargetBundle())) {
                $listFields[$field_name]['type'] = $field_definition->getType();
            }
        }

        return in_array($fieldId, array_keys($listFields));
    }
}