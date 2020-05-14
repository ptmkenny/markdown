<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\markdown\Form\SubformState;
use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use Drupal\markdown\Plugin\Markdown\SettingsInterface;
use Drupal\markdown\Traits\SettingsTrait;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Extension\Autolink\AutolinkExtension as LeagueAutolinkExtension;
use League\CommonMark\Extension\Autolink\InlineMentionParser as LeagueInlineMentionParser;

/**
 * Autolink extension.
 *
 * @MarkdownExtension(
 *   id = "league/commonmark-ext-autolink",
 *   label = @Translation("Autolink"),
 *   installed = "\League\CommonMark\Extension\Autolink\AutolinkExtension",
 *   description = @Translation("Automatically links URLs and email addresses even when the CommonMark <code>&lt;...&gt;</code> autolink syntax is not used."),
 *   url = "https://commonmark.thephpleague.com/extensions/autolinks/",
 * )
 */
class AutolinkExtension extends BaseExtension implements SettingsInterface, PluginFormInterface {

  use SettingsTrait {
    getConfiguration as getConfigurationTrait;
  }

  /**
   * Provides an alphanumeric id -> symbol map.
   *
   * @var string[]
   */
  protected static $symbols = [
    'at' => '@',
    'hash' => '#',
  ];

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'at' => [
        'map' => '',
        'entity_type_id' => 'user',
        'entity_label' => TRUE,
        'keep_symbol' => TRUE,
        'url' => 'https://www.drupal.org/u/%s',
        'url_title' => TRUE,
        'url_title_levels' => 1,
      ],
      'hash' => [
        'map' => '',
        'entity_type_id' => 'node',
        'entity_label' => TRUE,
        'keep_symbol' => TRUE,
        'url' => 'https://www.drupal.org/node/%s',
        'url_title' => TRUE,
        'url_title_levels' => 1,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\markdown\Form\SubformStateInterface $form_state */

    $entityTypes = \Drupal::entityManager()->getEntityTypeLabels(TRUE);

    foreach (static::$symbols as $name => $symbol) {
      $element[$name] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Inline Mention: @symbol', ['@symbol' => $symbol]),
      ];
      $symbolElement = &$element[$name];
      $symbolSubformState = SubformState::createForSubform($symbolElement, $element, $form_state);

      $symbolMap = [
        '' => $this->t('Nothing'),
        'entity' => $this->t('Entity'),
      ];
      if ($symbol === '@') {
        $symbolMap['github'] = $this->t('GitHub');
        $symbolMap['twitter'] = $this->t('Twitter');
      }
      $symbolMap['url'] = $this->t('Custom URL');

      $symbolElement += $this->createSettingElement("$name.map", [
        '#type' => 'select',
        '#title' => $this->t('Map'),
        '#options' => $symbolMap,
      ], $symbolSubformState);

      $symbolElement += $this->createSettingElement("$name.keep_symbol", [
        '#type' => 'checkbox',
        '#description' => $this->t('When enabled, the symbol will always be present at the beginning. If disabled, the symbol will be removed.'),
      ], $symbolSubformState);
      $symbolSubformState->addElementState($symbolElement['keep_symbol'], 'visible', 'map', ['!value' => '']);

      $symbolElement += $this->createSettingElement("$name.entity_type_id", [
        '#type' => 'select',
        '#title' => $this->t('Entity Type'),
        '#options' => $entityTypes,
      ], $symbolSubformState);
      $symbolSubformState->addElementState($symbolElement['entity_type_id'], 'visible', 'map', ['value' => 'entity']);

      $symbolElement += $this->createSettingElement("$name.entity_label", [
        '#type' => 'checkbox',
        '#description' => $this->t('When enabled, the matched text will be replaced with the entity label.'),
      ], $symbolSubformState);
      $symbolSubformState->addElementState($symbolElement['entity_label'], 'visible', 'map', ['value' => 'entity']);

      $symbolElement += $this->createSettingElement("$name.url", [
        '#type' => 'textfield',
        '#title' => $this->t('URL'),
        '#description' => $this->t('A URL to format text with. Use the token <code>%s</code> where it is needed. If you need to include an @name symbol (@symbol), use the URL encoded equivalent: <code>@urlEncodedSymbol</code>. Example: <code>https://example.com/search?q=@urlEncodedSymbol%s</code>.', [
          '@name' => $name,
          '@symbol' => $symbol,
          '@urlEncodedSymbol' => urlencode($symbol),
        ]),
      ], $symbolSubformState);
      $symbolSubformState->addElementState($symbolElement['url'], 'visible', 'map', ['value' => 'url']);

      $symbolElement += $this->createSettingElement("$name.url_title", [
        '#type' => 'checkbox',
        '#title' => $this->t('URL Title'),
        '#description' => $this->t('When enabled, the matched text will be replaced with the title of the URL. Note: this creates an HTTP request at the time of parsing the URL and may increase performance times, disable if URL is known to not be reachable from this site.'),
      ], $symbolSubformState);
      $symbolSubformState->addElementState($symbolElement['url_title'], 'visible', 'map', ['value' => 'url']);

      $symbolElement += $this->createSettingElement("$name.url_title_levels", [
        '#type' => 'select',
        '#title' => $this->t('URL Title Levels'),
        '#description' => $this->t('Select the maximum number of levels of the title to extract, delimited by pipes (|).'),
        '#options' => [
          0 => $this->t('Unlimited'),
          1 => 1,
          2 => 2,
          3 => 3,
          4 => 4,
          5 => 5,
        ]
      ], $symbolSubformState);
      $symbolSubformState->addElementState($symbolElement['url_title_levels'], 'visible', 'map', ['value' => 'url']);
      $symbolSubformState->addElementState($symbolElement['url_title_levels'], 'visible', 'url_title', ['checked' => TRUE]);
    }

    return $element;
  }

  public function getConfiguration() {
    $configuration = $this->getConfigurationTrait();
    foreach (static::$symbols as $name => $symbol) {
      if (empty($configuration['settings'][$name]['map'])) {
        unset($configuration['settings'][$name]);
        continue;
      }
      if ($configuration['settings'][$name]['map'] !== 'entity') {
        unset($configuration['settings'][$name]['entity_type_id']);
        unset($configuration['settings'][$name]['entity_label']);
      }
      if ($configuration['settings'][$name]['map'] !== 'url') {
        unset($configuration['settings'][$name]['url']);
        unset($configuration['settings'][$name]['url_title']);
        unset($configuration['settings'][$name]['url_title_levels']);
      }
    }
    return $configuration;
  }

  /**
   * Retrieves a URL page title.
   *
   * @param string $url
   *   The URL to retrieve the title from.
   *
   * @return string|false
   *   The URL title or FALSE if it could not be retrieved.
   */
  protected function getUrlTitle($url) {
    $response = \Drupal::httpClient()->get($url);
    if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
      /* @noinspection PhpComposerExtensionStubsInspection */
      $dom = new \DOMDocument();
      @$dom->loadHTML($response->getBody()->getContents());
      if (($title = $dom->getElementsByTagName('title')) && $title->length) {
        return Html::escape(trim(preg_replace('/\s+/', ' ', $title->item(0)->textContent)));
      }
    }
    return FALSE;
  }

  /**
   * Maps a handle to an entity.
   *
   * @param string $handle
   *   The handle, value of the user supplied text; passed by reference.
   * @param string $label
   *   The label that will be used to construct the link. Defaults to the
   *   handle prefixed with the symbol; passed by reference.
   * @param string $symbol
   *   The symbol that is used to denote that this was an inline mention.
   *
   * @return string|false
   *   The URL generated for the provided $handle. Returns FALSE if there
   *   was no entity matching $handle.
   *
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function mapHandleToEntity(&$handle, &$label, $symbol) {
    $name = array_search($symbol, static::$symbols, TRUE);
    $entityTypeId = $this->getSetting("$name.entity_type_id");
    $entityManager = \Drupal::entityManager();
    $storage = $entityManager->getStorage($entityTypeId);
    $entityType = $storage->getEntityType();
    $labelKey = $entityType->getKey('label') ?: $entityTypeId;

    // Users need a little special casing to assist with localing by username.
    if ($entityTypeId === 'user') {
      $labelKey = 'name';
    }

    $entity = is_numeric($handle) ? $storage->load($handle) : current($storage->loadByProperties([$labelKey => $handle]));
    if (!$entity) {
      return FALSE;
    }

    if ($this->getSetting("$name.entity_label")) {
      $label = $entity->label();
    }

    // Keep symbol on label.
    if ($this->getSetting("$name.keep_symbol") && $label[0] !== $symbol) {
      $label = "$symbol$label";
    }
    // Otherwise remove it.
    elseif ($label[0] === $symbol) {
      $label = substr($label, 1);
    }

    return (string) $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
  }

  /**
   * Maps a handle to a specific URL.
   *
   * @param string $handle
   *   The handle, value of the user supplied text; passed by reference.
   * @param string $label
   *   The label that will be used to construct the link. Defaults to the
   *   handle prefixed with the symbol; passed by reference.
   * @param string $symbol
   *   The symbol that is used to denote that this was an inline mention.
   *
   * @return string
   *   The URL generated for the provided $handle. Returns FALSE if there
   *   was no entity matching $handle.
   */
  public function mapHandleToUrl(&$handle, &$label, $symbol) {
    $name = array_search($symbol, static::$symbols, TRUE);
    $url = str_replace('%s', $handle, $this->getSetting("$name.url"));

    if ($this->getSetting("$name.url_title") && ($title = $this->getUrlTitle($url))) {
      if ($urlTitleLevels = $this->getSetting("$name.url_title_levels")) {
        $parts = array_map('trim', explode('|', $title));
        $title = implode(' | ', array_slice($parts, 0, $urlTitleLevels));
      }
      $label = $title;
    }

    // Keep symbol on label.
    if ($this->getSetting("$name.keep_symbol") && $label[0] !== $symbol) {
      $label = "$symbol$label";
    }
    // Otherwise remove it.
    elseif ($label[0] === $symbol) {
      $label = substr($label, 1);
    }

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function register(ConfigurableEnvironmentInterface $environment) {
    $environment->addExtension(new LeagueAutolinkExtension());
    foreach (static::$symbols as $name => $symbol) {
      if ($map = $this->getSetting("$name.map")) {
        switch ($map) {
          case 'entity':
            $environment->addInlineParser(InlineMentionParser::create($symbol, [$this, 'mapHandleToEntity']));
            break;

          case 'github':
            $environment->addInlineParser(LeagueInlineMentionParser::createGithubHandleParser());
            break;

          case 'twitter':
            $environment->addInlineParser(LeagueInlineMentionParser::createTwitterHandleParser());
            break;

          case 'url':
            $environment->addInlineParser(InlineMentionParser::create($symbol, [$this,
              'mapHandleToUrl'
            ]));
            break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsKey() {
    return FALSE;
  }

}
