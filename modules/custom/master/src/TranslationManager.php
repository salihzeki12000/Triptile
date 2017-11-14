<?php

namespace Drupal\master;

use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\StringTranslation\TranslationManager as CoreTranslationManager;
use Drupal\Component\Gettext\PoStreamReader;
use Drupal\Component\Gettext\PoItem;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxy;
Use Drupal\Core\Link;

class TranslationManager extends CoreTranslationManager {

  /**
   * Store all translations from file.
   *
   * @var array
   */
  protected $translationsStore;

  /**
   * Return true if file exists already and false in another way.
   *
   * @var bool
   */
  protected $isFilesExists;

  protected $isFileRead;

  /**
   * A request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Request represents an HTTP request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch;
   */
  protected $currentRouteMatch;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Path to export translation files.
   *
   * @var string
   */
  protected $folderPath;

  /**
   * Constructs a TranslationManagerCustom object.
   *
   * @param \Drupal\Core\Language\LanguageDefault $default_language
   *   The default language.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   * @throws \Exception
   */
  public function __construct(LanguageDefault $default_language, RequestStack $request_stack, ConfigFactoryInterface $config_factory, AccountProxy $current_user) {
    parent::__construct($default_language);
  }

  /**
   * {@inheritdoc}
   */
  public function getStringTranslation($langcode, $string, $context) {
    $translation = parent::getStringTranslation($langcode, $string, $context);
    if (!$this->isFileRead) {
      $this->readTranslationFile();
    }
    $config = $this->configFactory->get('master.settings');
    if ($config->get('record_translations')) {
      $user_roles = $this->currentUser->getRoles();
      if (!in_array('administrator', $user_roles) && $this->currentUser->hasPermission('allow recording translation files')) {
        if (!$this->isFilesExists) {
          $this->createTranslationFiles();
        }
        $key = $context ? $string . PluralTranslatableMarkup::DELIMITER . $context : $string;
        if (!isset($this->translationsStore[$key])) {
          $this->translationsStore[$key] = [
            'string' => $string,
            'context' => $context,
            'translation' => $translation,
          ];
          $item = $this->createPoItem($langcode, $string, $context, $translation);
          $writer = new PoStreamWriterCustom();
          $writer->setUri($this->folderPath . 'translations.po');
          $writer->open();
          $writer->writeItem($item);
          $writer->close();
          $this->writeToExportFile($langcode, $string, $context, $translation);
        }
      }
    }
    return $translation;
  }


  /**
   * Create object of PoItem class.
   *
   * @param $langcode
   * @param $string
   * @param $context
   * @param $translation
   * @return \Drupal\Component\Gettext\PoItem
   */
  protected function createPoItem($langcode, $string, $context, $translation) {
    if (strpos($string, PluralTranslatableMarkup::DELIMITER)) {
      $string = explode(PluralTranslatableMarkup::DELIMITER, $string);
    }
    if (strpos($translation, PluralTranslatableMarkup::DELIMITER)) {
      $translation = explode(PluralTranslatableMarkup::DELIMITER, $translation);
    }
    $plural = FALSE;

    if (is_array($string)) {
      // Sort plural variants by their form index.
      ksort($string);
      if (empty($translation)) {
        $translation[] = "";
      }
      $plural = TRUE;
    }

    $item = new PoItem();
    $item->setContext($context ? : '');
    $item->setSource($string);
    $item->setTranslation($translation);
    $item->setPlural($plural);
    $item->setLangcode('en');

    return $item;
  }

  /**
   * Handler writer.
   *
   * @param $langcode
   * @param $string
   * @param $context
   * @param $translation
   */
  protected function writeToExportFile($langcode, $string, $context, $translation) {
    $route_name = $this->currentRouteMatch->getRouteName();
    $file = $this->folderPath . 'translations.html'; // see below for source
    $doc = \phpQuery::newDocumentFileHTML($file);
    $body = pq('body');
    $selector = "table.route[data-route-name=\"$route_name\"]";
    $route_element = $body->find($selector);
    $row = $this->getRow($langcode, $string, $context, $translation);
    if (empty($route_element->elements)){
      $body->append($this->getRouteTable($route_name));
      pq($selector)->append($row);
    }
    else {
      $route_element->append($row);
    }
    $handle = fopen($file, 'w');
    fwrite($handle, $doc->html());
    fclose($handle);
  }

  /**
   * Gets a html table for file translations.html
   *
   * @param $route_name
   * @return string
   */
  protected function getRouteTable($route_name) {
    $output = "<table class=\"route\" data-route-name=\"$route_name\">";
    $output .= "<thead><tr>";
    $output .= "<th>Source</th>";
    $output .= "<th>Context</th>";
    $output .= "<th>English variant</th>";
    $output .= "<th>URL</th>";
    $output .= "</tr></thead></table>";

    return $output;
  }

  /**
   * Gets a html row for table. File translations.html
   *
   * @param $langcode
   * @param $string
   * @param $context
   * @param $translation
   * @return string
   */
  protected function getRow($langcode, $string, $context, $translation) {
    $url = Url::fromUri($this->request->getUri());
    $link = Link::fromTextAndUrl($this->request->getRequestUri(), $url)->toString();
    $output = "<tr class=\"row\">";
    $output .= "<td class=\"source\">$string</td>";
    $output .= "<td class=\"context\">$context</td>";
    $output .= "<td class=\"english\">$translation</td>";
    $output .= "<td class=\"url\">$link</td>";
    $output .= "</tr>";

    return $output;
  }

  /**
   * Create/Reset translations files.
   */
  protected function createTranslationFiles() {
    if (file_prepare_directory($this->folderPath, FILE_CREATE_DIRECTORY)) {
      $template_path = drupal_get_path('module', 'master') . '/templates/translations_export/translations.po';
      file_unmanaged_copy($template_path, $this->folderPath . 'translations.po', FILE_EXISTS_REPLACE);
      $template_path = drupal_get_path('module', 'master') . '/templates/translations_export/translations.html';
      file_unmanaged_copy($template_path, $this->folderPath . 'translations.html', FILE_EXISTS_REPLACE);
      $this->isFilesExists = true;
    }
  }

  protected function readTranslationFile() {
    $this->requestStack = \Drupal::service('request_stack');
    $this->request = $this->requestStack->getCurrentRequest();
    $this->currentRouteMatch = new CurrentRouteMatch($this->requestStack);
    $this->configFactory = \Drupal::service('config.factory');
    $this->currentUser = \Drupal::service('current_user');
    $this->folderPath = 'public://translations_export/';
    $user_roles = $this->currentUser->getRoles();
    if (!in_array('administrator', $user_roles) && $this->currentUser->hasPermission('allow recording translation files')) {
      if (file_exists($this->folderPath . 'translations.po') && file_exists($this->folderPath . 'translations.html')) {
        $this->isFilesExists = true;
        // Instantiate and initialize the stream reader for this file.
        $reader = new PoStreamReader();
        $reader->setLangcode('en');
        $reader->setURI($this->folderPath . 'translations.po');
        $reader->open();
        $reader->getHeader();
        while ($item = $reader->readItem()) {
          if ($item->isPlural()) {
            $item->setSource(implode(PluralTranslatableMarkup::DELIMITER, $item->getSource()));
            $item->setTranslation(implode(PluralTranslatableMarkup::DELIMITER, $item->getTranslation()));
          }
          $string = $item->getSource();
          $context = $item->getContext();
          $translation = $item->getTranslation();
          $key = $context ? $string . PluralTranslatableMarkup::DELIMITER . $context : $string;
          $this->translationsStore[$key] = [
            'string' => $string,
            'context' => $context,
            'translation' => $translation,
          ];
        }
        $this->isFileRead = true;
      }
      else {
        $this->isFilesExists = false;
      }
    }
  }

}
