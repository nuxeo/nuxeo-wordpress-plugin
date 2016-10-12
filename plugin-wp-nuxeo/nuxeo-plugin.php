<?php
/*
Plugin Name: Plugin Nuxeo for Wordpress
Plugin URI: https://github.com/nuxeo/plugin-wp-nuxeo
Description: Wordpress Plugin for Nuxeo through Automation API.
Version: 1.0.0-SNAPSHOT
Author: nuxeo
Author URI: https://github.com/nuxeo

 * (C) Copyright 2015 Nuxeo SA (http://nuxeo.com/) and contributors.
 *
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the GNU Lesser General Public License
 * (LGPL) version 2.1 which accompanies this distribution, and is available at
 * http://www.gnu.org/licenses/lgpl-2.1.html
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * Contributors:
 *     Laurent Dreuillat <ldreuillat@nuxeo.com>
 *     Pierre-Gildas MILLON <pgmillon@nuxeo.com>
 */
require_once __DIR__ . '/vendor/autoload.php';

class AttributesBag extends ArrayObject {

  public function offsetGet($index, $default=null) {
    return $this->offsetExists($index)?parent::offsetGet($index):$default;
  }

  public function keys() {
    return array_keys($this->getArrayCopy());
  }

}

class NuxeoWordPressPlugin {

  const SHORTCODE_TAG = 'nuxeo';

  private $client_instance = null;

  private $twig = null;

  public static function setup() {
    $instance = new NuxeoWordPressPlugin();

    add_shortcode(NuxeoWordPressPlugin::SHORTCODE_TAG, array($instance, 'handle'));
    add_action('plugins_loaded', array($instance, 'download_nuxeo_document'));
    add_action('admin_menu', array($instance, 'admin_menu'));

    if(is_admin()) {
      add_action('admin_head', array($instance, 'admin_head'));
      add_action('admin_enqueue_scripts', array($instance, 'admin_enqueue_scripts'));
    }
  }

  /**
   * @param array $attr
   * @param string $content
   * @return string
   *
   * Usage:
   *   [nuxeo path="/my particular/folder name/"]                  # Display the content of a folderish document based on its path (under the configured domain path)
   *   [nuxeo type="File"]                                         # Display Documents whose type matches
   *   [nuxeo nxquery="SELECT * FROM File WHERE ..."]              # Display Documents based on the NXQL query
   *   [nuxeo name="Agenda%.doc"]                                  # Docs whose name matches. May include wildcard character '%'.
   *   [nuxeo doc_id="c5e890d3-87f5-42cb-abe4-1ed9d0d2600c"]       # Display the detail of a document based on its id
   *   [nuxeo folderish_id="05cdeaa1-e57d-450a-8347-1600206e7cce"] # Display the content of a folderish document based on its id
   */
  public function handle($attr, $content = null) {
    $attributes = new AttributesBag($attr);

    $display_nxql = get_option('nx_display_query');

    switch(true) {
      case array_key_exists('nxquery', $attributes):
        return $this->display_nuxeo_query($attributes['nxquery'], $display_nxql);
      case array_key_exists('doc_id', $attributes):
        return $this->display_nuxeo_document_by_Id($attributes['doc_id'], $display_nxql);
      case array_key_exists('folderish_id', $attributes):
        return $this->display_nuxeo_folderish_by_Id(
          $attributes['folderish_id'],
          $attributes['name'],
          $attributes['type'],
          $display_nxql);
      case sizeof(array_intersect(array('path', 'name', 'type'), $attributes->keys())) > 0:
        return $this->display_nuxeo_document(
          $attributes['path'],
          $attributes['type'],
          $attributes['name'],
          $display_nxql);
    }

    return "";
  }

  /**
   * @return \Nuxeo\Automation\Client\NuxeoSession
   */
  protected function getClient() {
    if(null === $this->client_instance) {
      $repo_url = get_option('nx_repository_url');
      $repo_username = get_option('nx_username');
      $repo_password = get_option('nx_password');

      $nxclient = new \Nuxeo\Automation\Client\NuxeoPhpAutomationClient($repo_url.'/site/automation');
      $this->client_instance = $nxclient->getSession($repo_username, $repo_password);

      /*
       * Workaround for self-signed certificates
       * WARNING: this is highly not recommended
       */
//      $guzzleClientProperty = (new ReflectionObject($this->client_instance))->getProperty('client');
//      $guzzleClientProperty->setAccessible(true);
//
//      /** @var \Guzzle\Http\Client $guzzleClient */
//      $guzzleClient = $guzzleClientProperty->getValue($this->client_instance);
//      $guzzleClient->setSslVerification(false);
    }

    return $this->client_instance;
  }

  /**
   * @return Twig_Environment
   */
  protected function getTwig() {
    if(null === $this->twig) {
      $loader = new Twig_Loader_Array(array());
      $loader->setTemplate('nx_object_list', file_get_contents(__DIR__ . '/resources/nx_object_list.twig'));
      $loader->setTemplate('nx_settings', file_get_contents(__DIR__ . '/resources/nx_settings.twig'));

      $this->twig = new Twig_Environment($loader);
      $this->twig->addFilter(new Twig_SimpleFilter('nx_parse_date', array($this, 'parse_date_filter')));
      $this->twig->addFilter(new Twig_SimpleFilter('nx_url', array($this, 'document_url_filter')));
      $this->twig->addFunction(new Twig_SimpleFunction('settings_fields', 'settings_fields'));
      $this->twig->addFunction(new Twig_SimpleFunction('do_settings_sections', 'do_settings_sections'));
      $this->twig->addFunction(new Twig_SimpleFunction('get_option', 'get_option'));
      $this->twig->addFunction(new Twig_SimpleFunction('_e', '_e'));
    }

    return $this->twig;
  }

  /**
   * @param string $date
   * @return DateTime
   */
  function parse_date_filter($date) {
    return DateTime::createFromFormat("Y/m/d", $date);
  }

  /**
   * @param \Nuxeo\Automation\Client\NuxeoDocument $document
   * @return string
   */
  function document_url_filter($document) {
    return sprintf('%s?nx_doc_id=%s',
      get_option('siteurl'),
      $document->getUid());
  }

  /**
   * Get documents details based on a NXQL Query
   * @param string $nxquery
   * @param bool $display_nxql
   * @return string
   */
  function display_nuxeo_query($nxquery, $display_nxql=false) {
    $nxsession = $this->getClient();

    try {
      $docList = $nxsession->newRequest("Document.Query")->set('params', 'query', "$nxquery")->setSchema($schema = '*')->sendRequest();
    } catch(\Nuxeo\Automation\Client\Internals\NuxeoClientException $e) {
      return $e->getPrevious()->getMessage();
    }

    return $this->getTwig()->render('nx_object_list', array(
      'trace' => $display_nxql ? $nxquery : "",
      'docs' => $docList->getDocumentList()
    ));
  }

  /**
   * @param string $path
   * @param $type
   * @param $name
   * @param bool $display_nxql
   * @return string
   */
  function display_nuxeo_document($path=null, $type=null, $name=null, $display_nxql=false) {
    $query = "SELECT * FROM Document WHERE ";
    $clauses = array(
      "ecm:mixinType != 'Folderish'",
      "ecm:mixinType != 'HiddenInNavigation'",
      "ecm:isCheckedInVersion = 0",
      "ecm:currentLifeCycleState != 'deleted'",
    );

    if($path) {
      $clauses[] = "ecm:path STARTSWITH '$path'";
    }

    if($name) {
      $clauses[] = "dc:title LIKE '$name%'";
    }

    if($type) {
      $clauses[] = "ecm:primaryType = '$type'";
    }

    $query .= join(' AND ', $clauses);

    return $this->display_nuxeo_query($query, $display_nxql);
  }

  /**
   * @param string $doc_id
   * @param bool $display_nxql
   * @return string
   */
  function display_nuxeo_document_by_Id($doc_id, $display_nxql=false) {
    $query = "SELECT * FROM Document WHERE ecm:mixinType != 'Folderish' " .
      "AND ecm:mixinType != 'HiddenInNavigation' AND ecm:isCheckedInVersion = 0 " .
      "AND ecm:currentLifeCycleState != 'deleted' AND ecm:uuid = '$doc_id'";

    return $this->display_nuxeo_query($query, $display_nxql);
  }

  /**
   * @param string $folderish_id
   * @param string $name
   * @param string $type
   * @param bool $display_nxql
   * @return string
   */
  function display_nuxeo_folderish_by_Id($folderish_id, $name=null, $type=null, $display_nxql=false) {
    $query = "SELECT * FROM Document WHERE ecm:mixinType != 'Folderish' " .
      "AND ecm:mixinType != 'HiddenInNavigation' AND ecm:isCheckedInVersion = 0 " .
      "AND ecm:currentLifeCycleState != 'deleted' AND ecm:parentId = '$folderish_id'";

    if ($name) {
      $query .= " AND dc:title LIKE '$name%'";
    }

    if($type) {
      $query .= " AND ecm:primaryType = '$type'";
    }

    return $this->display_nuxeo_query($query, $display_nxql);
  }

  function download_nuxeo_document() {
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $validation = \Symfony\Component\Validator\Validation::createValidator();
    $nxsession = $this->getClient();

    if($request->query->has('nx_doc_id')) {
      $doc_id = $request->query->get('nx_doc_id');
      $violations = $validation->validate($doc_id, array(
        new \Symfony\Component\Validator\Constraints\Uuid()
      ));

      if(0 === count($violations)) {
        $doc = $nxsession
          ->newRequest("Document.Fetch")
          ->set('params', 'value', $doc_id)->setSchema($schema = 'dublincore,file')
          ->sendRequest()->getDocument(0);
        try {
          $file_content = $nxsession
            ->newRequest("Blob.Get")
            ->set('input', 'doc:' . $doc_id)
            ->sendRequest();

          $file_info = new AttributesBag($doc->getProperty('file:content'));
          $response = new \Symfony\Component\HttpFoundation\Response($file_content);

          $response->setLastModified(new DateTime($doc->getProperty('dc:modified')));
          $response->headers->add(array(
            'Content-Disposition' => $response->headers->makeDisposition(
              \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
              $doc->getProperty('file:filename')),
            'Content-Length' => $file_info->offsetGet('length'),
            'Content-Type' => $file_info->offsetGet('mime-type', 'application/octet-stream')
          ));
        } catch(Exception $e) {
          $response = new \Symfony\Component\HttpFoundation\Response($e->getMessage());
        }
        $response->send();
        exit;
      }
    }
  }

  /**
   * @param array $plugin_array
   * @return array
   */
  function mce_external_plugins($plugin_array) {
    $plugin_array["nuxeo"] = plugins_url('js/mce-button.js', __FILE__);
    return $plugin_array;
  }

  /**
   * @param array $buttons
   * @return array
   */
  function mce_buttons($buttons) {
    array_push($buttons, "nuxeo");
    return $buttons;
  }

  /**
   * Register Nuxeo settings with WordPress
   */
  function register_nuxeo_settings() {
    register_setting('nuxeo-settings-group', 'nx_repository_url');
    register_setting('nuxeo-settings-group', 'nx_workspace_root');
    register_setting('nuxeo-settings-group', 'nx_username');
    register_setting('nuxeo-settings-group', 'nx_password');
    register_setting('nuxeo-settings-group', 'nx_display_query');
    add_settings_section(
      'nuxeo_repository',
      'Nuxeo Repository Settings',
      array($this, 'render_settings_header'),
      'nuxeo_repository');
  }

  function render_settings() {
    if ( !current_user_can( 'manage_options' ) )  {
      wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    $this->getTwig()->display('nx_settings');
  }

  function render_settings_header() {
    echo "<p>Provide connection informations for a Nuxeo repository.</p>";
  }

  function admin_menu() {
    add_options_page(
      __('Nuxeo Options', 'menu-nuxeo'),
      __('Nuxeo Options', 'menu-nuxeo'),
      'manage_options',
      'basic-nuxeo-settings',
      array($this, 'render_settings'));

    add_action('admin_init', array($this, 'register_nuxeo_settings'));
  }

  function admin_head() {
    // check user permissions
    if(!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
      return;
    }

    // check if WYSIWYG is enabled
    if('true' == get_user_option('rich_editing')) {
      add_filter('mce_external_plugins', array($this, 'mce_external_plugins'));
      add_filter('mce_buttons', array($this, 'mce_buttons'));
    }
  }

  function admin_enqueue_scripts() {
    wp_enqueue_style('nuxeo_shortcode', plugins_url('css/mce-button.css', __FILE__));
  }

}

NuxeoWordPressPlugin::setup();
