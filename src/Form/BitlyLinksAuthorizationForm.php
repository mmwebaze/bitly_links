<?php

namespace Drupal\bitly_links\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\State;

/**
 * Class BitlyLinksAuthorizationForm.
 */
class BitlyLinksAuthorizationForm extends FormBase
{
    protected $state;
    public function __construct(State $state)
    {
        $this->state = $state;
    }
    public static function create(ContainerInterface $container) {
        return new static(
            //$container->get('bitly_links.manager_service'),
            //$container->get( 'config.factory'),
            $container->get('state')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'bitly_links_authorize_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        //$config = $this->config('bitly_links.bitlylinksconfig');
        $redirectUri = $this->state->get('bitly_links_redirect_uri');

        $form['bitly_links'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('General bitly Settings'),
        );
        $form['bitly_links']['base_url'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Bitly authorize url'),
            '#default_value' => /*$config->get('base_url')*/'https://bitly.com/oauth/authorize',
            '#required' => TRUE,
        );
        $form['bitly_links']['client_id'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('client id'),
            '#default_value' => $this->state->get('bitly_links_client_id'),
            '#required' => TRUE,
        );
        $form['bitly_links']['client_secret'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('client secret'),
            '#default_value' => $this->state->get('bitly_links_client_secret'),
            '#required' => TRUE,
        );
        /*$form['bitly_links']['auth_token'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Bitly Auth Token'),
            '#default_value' => $config->get('auth_token'),
            //'#required' => TRUE,
        );*/
        $form['bitly_links']['redirect_uri'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Redirect uri'),
            '#default_value' => isset($redirectUri) ? $redirectUri : $this->getRequest()->getSchemeAndHttpHost().'/bitly_links/oauthPage',
            '#required' => TRUE,
        );
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Start authorization'),
            '#button_type' => 'primary',
        );
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
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->state->set('bitly_links_base_url', $form_state->getValue('base_url'));
        $this->state->set('bitly_links_client_id', $form_state->getValue('client_id'));
        $this->state->set('bitly_links_client_secret', $form_state->getValue('client_secret'));
        $this->state->set('bitly_links_redirect_uri', $form_state->getValue('redirect_uri'));

        $form_state->setRedirect('bitly_links.app_authorization');
        //parent::submitForm($form, $form_state);
       /* $this->config('bitly_links.bitlylinksconfig')
            ->set('base_url', $form_state->getValue('base_url'))
            ->set('client_id', $form_state->getValue('client_id'))
            ->set('client_secret', $form_state->getValue('client_secret'))
            /*->set('auth_token', $form_state->getValue('auth_token'))
            ->set('redirect_uri', $form_state->getValue('redirect_uri'))
            ->save();*/
    }
}
