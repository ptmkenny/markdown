<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use Drupal\markdown\Plugin\Markdown\SettingsInterface;
use Drupal\markdown\Traits\SettingsTrait;
use League\CommonMark\Inline\Element\Link;
use League\CommonMark\Inline\Parser\InlineParserInterface;
use League\CommonMark\InlineParserContext;

/**
 * @MarkdownExtension(
 *   id = "hash_autolinker",
 *   label = @Translation("# Autolinker"),
 *   installed = TRUE,
 *   description = @Translation("Automatically link commonly used references that come after a hash character (#) without having to use the link syntax."),
 * )
 */
class HashAutolinker extends BaseExtension implements InlineParserInterface, SettingsInterface, PluginFormInterface {

  use SettingsTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'type' => 'node',
      'node_title' => TRUE,
      'url' => 'https://www.drupal.org/node/[text]',
      'url_title' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\markdown\Form\SubformStateInterface $form_state */

    $element += $this->createSettingElement('type', [
      '#type' => 'select',
      '#title' => $this->t('Map text to'),
      '#options' => [
        'node' => $this->t('Node'),
        'url' => $this->t('URL'),
      ],
    ], $form_state);

    $element += $this->createSettingElement('node_title', [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace text with title of node'),
      '#description' => $this->t('If enabled, it will replace the matched text with the title of the node.'),
    ], $form_state);
    $form_state->addElementState($element['node_title'], 'visible', 'type', ['value' => 'node']);

    $element += $this->createSettingElement('url', [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('A URL to format text with. Use the token "[text]" where it is needed. If you need to include the #, use the URL encoded equivalent: <code>%23</code>. Example: <code>https://twitter.com/search?q=%23[text]</code>.'),
    ], $form_state);
    $form_state->addElementState($element['url'], 'visible', 'type', ['value' => 'url']);

    $element += $this->createSettingElement('url_title', [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace text with title of URL'),
      '#description' => $this->t('If enabled, it will replace the matched text with the title of the URL.'),
    ], $form_state);
    $form_state->addElementState($element['url_title'], 'visible', 'type', ['value' => 'url']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Intentionally do nothing. This is just required to be implemented.
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Intentionally do nothing. This is just required to be implemented.
  }

  /**
   * {@inheritdoc}
   */
  public function settingsKey() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @noinspection PhpLanguageLevelInspection
   * @noinspection PhpInappropriateInheritDocUsageInspection
   */
  public function getCharacters(): array {
    return ['#'];
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
      // @todo Add PHP extension requirements to definitions and check for it.
      $dom = new \DOMDocument();
      @$dom->loadHTML($response->getBody()->getContents());
      if (($title = $dom->getElementsByTagName('title')) && $title->length) {
        return Html::escape(trim(preg_replace('/\s+/', ' ', $title->item(0)->textContent)));
      }
    }
    return FALSE;
  }

  /**
   * Retrieves an Entity object for the current route.
   *
   * @return \Drupal\Core\Entity\EntityInterface|void
   *   An Entity object or NULL if none could be found.
   */
  protected function currentRouteEntity() {
    $route_match = \Drupal::routeMatch();
    foreach ($route_match->getParameters()->all() as $item) {
      if ($item instanceof EntityInterface) {
        return $item;
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * @noinspection PhpLanguageLevelInspection
   * @noinspection PhpInappropriateInheritDocUsageInspection
   */
  public function parse(InlineParserContext $inline_context): bool {
    $cursor = $inline_context->getCursor();

    // The # symbol must not have any other characters immediately prior.
    $previous_char = $cursor->peek(-1);
    if ($previous_char !== NULL && $previous_char !== ' ' && $previous_char !== '[') {
      // peek() doesn't modify the cursor, so no need to restore state first.
      return FALSE;
    }

    // Save the cursor state in case we need to rewind and bail.
    $previous_state = $cursor->saveState();

    // Advance past the # symbol to keep parsing simpler.
    $cursor->advance();

    // Parse the handle.
    $text = $cursor->match('/^[^\s\]]+/');
    $url = FALSE;
    $title = FALSE;
    $type = $this->getSetting('type');

    // @todo Make entity type abstract and comment aware.
    if ($type === 'node' && is_numeric($text) && ($node = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($text))) {
      $url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
      if ($this->getSetting('node_title') && ($title = $node->label())) {
        $text = $title;
      }
      else {
        $text = "#$text";
      }
    }
    elseif ($type === 'url' && ($url = $this->getSetting('url')) && strpos($url, '[text]') !== FALSE) {
      $url = str_replace('[text]', $text, $url);
      if ($this->getSetting('url_title') && ($title = $this->getUrlTitle($url))) {
        $text = $title;
        $title = FALSE;
      }
    }
    else {
      $text = FALSE;
    }

    // Regex failed to match; this isn't a valid @ handle.
    if (empty($text) || empty($url)) {
      $cursor->restoreState($previous_state);
      return FALSE;
    }

    $inline_context->getContainer()->appendChild(new Link($url, $text, $title));

    return TRUE;
  }

}
