<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Url;
use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use Drupal\markdown\Plugin\Markdown\CommonMark\RendererInterface;
use Drupal\markdown\Plugin\Markdown\MarkdownGuidelinesAlterInterface;
use Drupal\markdown\Plugin\Markdown\SettingsInterface;
use Drupal\markdown\Traits\SettingsTrait;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;
use League\CommonMark\Inline\Element\AbstractInline;
use League\CommonMark\Inline\Element\Link;
use League\CommonMark\Inline\Renderer\InlineRendererInterface;

/**
 * @MarkdownExtension(
 *   id = "enhanced_links",
 *   label = @Translation("Enhanced Links"),
 *   installed = TRUE,
 *   description = @Translation("Extends CommonMark to provide additional enhancements when rendering links."),
 * )
 */
class LinkRenderer extends BaseExtension implements RendererInterface, InlineRendererInterface, MarkdownGuidelinesAlterInterface, SettingsInterface, PluginFormInterface {

  use SettingsTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'external_new_window' => TRUE,
      'internal_host_whitelist' => \Drupal::request()->getHost(),
      'no_follow' => 'external',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\markdown\Form\SubformStateInterface $form_state */

    if (!empty($element['#description'])) {
      $element['#description'] = '<p>' . $element['#description'] . '</p>';
    }
    elseif (!isset($element['#description'])) {
      $element['#description'] = '';
    }

    $element['#description'] .= '<p><strong>' . $this->t('NOTE: These settings only apply to markdown links rendered using the parser, if a raw HTML <code>&lt;a&gt;</code> tag is used, then these settings will not be applied to them.') . '</strong></p>';

    $element += $this->createSettingElement('internal_host_whitelist', [
      '#type' => 'textarea',
      '#description' => $this->t('Allows additional host names to be treated as "internal" when they would normally be considered as "external". This is useful in cases where a multi-site is using different sub-domains. The current host name, %host, will always be considered "internal" (even if removed from this list). Enter one host name per line. No regular expressions are allowed, just exact host name matches.', [
        '%host' => \Drupal::request()->getHost(),
      ]),

    ], $form_state);

    $element += $this->createSettingElement('external_new_window', [
      '#type' => 'checkbox',
      '#title' => $this->t('Open external links in new windows'),
      '#description' => $this->t('When this setting is enabled, any link that does not contain one of the above internal whitelisted host names will automatically be considered as an "external" link. All external links will then have the <code>target="_blank"</code> attribute and value added to it.'),
    ], $form_state);

    $element += $this->createSettingElement('no_follow', [
      '#type' => 'select',
      '#title' => $this->t('Add <code>rel="nofollow"</code> to'),
      '#description' => $this->t('The rel="nofollow" attribute and value instructs some search engines that the link should not influence the ranking of the link\'s target in the search engine\'s index.'),
      '#options' => [
        '' => $this->t('None of the links'),
        'all' => $this->t('All of the links'),
        'external' => $this->t('External links only'),
        'internal' => $this->t('Internal links only'),
      ],
    ], $form_state);

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
   */
  public function alterGuidelines(array &$guides = []) {
    if (!isset($guides['links']['items'][0]['description'])) {
      $guides['links']['items'][0]['description'] = [];
    }
    elseif (isset($guides['links']['items'][0]['description']) && !is_array($guides['links']['items'][0]['description'])) {
      $guides['links']['items'][0]['description'] = [$guides['links']['items'][0]['description']];
    }

    if ($this->getSetting('external_new_window')) {
      $guides['links']['items'][0]['description'][] = '<p>' . $this->t('All external links will open in a new window or tab.') . '</p>';
      $guides['links']['items'][0]['tags']['a'][] = '[' . $this->t('External link opens in new window') . '](http://example.com)';
    }
    else {
      $guides['links']['items'][0]['tags']['a'][] = '[' . $this->t('External link') . '](http://example.com)';
    }

    if ($this->getSetting('no_follow') === 'all') {
      $guides['links']['items'][0]['description'][] = '<p>' . $this->t('All links will have the <code>rel="nofollow"</code> attribute applied to it.') . '</p>';
    }
    elseif ($this->getSetting('no_follow') === 'external') {
      $guides['links']['items'][0]['description'][] = '<p>' . $this->t('All external links will have the <code>rel="nofollow"</code> attribute applied to it.') . '</p>';
    }
    elseif ($this->getSetting('no_follow') === 'internal') {
      $guides['links']['items'][0]['description'][] = '<p>' . $this->t('All internal links will have the <code>rel="nofollow"</code> attribute applied to it.') . '</p>';
    }

    // Determine the internal whitelist host names.
    $hosts = preg_split("/\r\n|\n/", $this->getSetting('internal_host_whitelist'), -1, PREG_SPLIT_NO_EMPTY);

    // Ensure that the site's base url host name is always in this whitelist.
    $base_host = \Drupal::request()->getHost();
    $key = array_search($base_host, $hosts);
    if ($key === FALSE) {
      $hosts[] = $base_host;
    }

    // Only show this description if there are multiple host names.
    if (count($hosts) > 1) {
      foreach ($hosts as &$host) {
        $host = '<code>' . Html::escape($host) . '</code>';
      }
      $guides['links']['items'][0]['description'][] = '<p>' . $this->t('Links with the following URL host names will be treated as "internal" links: !hosts.', ['!hosts' => implode(', ', $hosts)]) . '</p>';
    }

    $base_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $site_name = \Drupal::config('system.site')->get('name');

    $guides['links']['items'][] = [
      'title' => $this->t('Manual links (using raw HTML)'),
      'description' => $this->t('The above examples are only for links using the Markdown syntax. Manually defined links using raw HTML are always processed "as is". You will be responsible for adding all attributes and values. Suffice it to say, it is always best to avoid using raw HTML and instead use the Markdown syntax whenever possible.'),
      'tags' => [
        'a' => [
          "<a href=\"$base_url\">$site_name</a>",
          "<a href=\"http://example.com\">External link</a>",
          "<a href=\"$base_url\" target=\"_blank\" rel=\"nofollow\">$site_name</a>",
          "<a href=\"http://example.com\" target=\"_blank\" rel=\"nofollow\">External link</a>",
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function rendererClass() {
    return Link::class;
  }

  /**
   * {@inheritdoc}
   */
  public function render(AbstractInline $inline, ElementRendererInterface $html_renderer) {
    if (!($inline instanceof Link)) {
      throw new \InvalidArgumentException('Incompatible inline type: ' . get_class($inline));
    }

    $attributes = $inline->getData('attributes', []);

    // Retrieve the URL.
    $url = $inline->getUrl();
    $external = $this->isExternalUrl($url);
    $attributes['href'] = $url;

    // Make external links open in a new window.
    if ($this->getSetting('external_new_window') && $external) {
      $attributes['target'] = '_blank';
    }

    // Add rel="nofollow".
    $no_follow = $this->getSetting('no_follow');
    if ($no_follow === 'all' || ($external && $no_follow === 'external') || (!$external && $no_follow === 'internal')) {
      $attributes['rel'] = 'nofollow';
    }

    if (isset($inline->data['title'])) {
      $attributes['title'] = Html::escape($inline->data['title']);;
    }

    return new HtmlElement('a', $attributes, $html_renderer->renderInlines($inline->children()));
  }

  /**
   * Determines if a URL is external to current host.
   *
   * @param string $url
   *   The URL to verify.
   *
   * @return bool
   *   TRUE or FALSE
   */
  private function isExternalUrl($url) {
    $url_host = parse_url($url, PHP_URL_HOST);

    // Only process URLs that actually have a host (e.g. not fragments).
    if (!isset($url_host) || empty($url_host)) {
      return FALSE;
    }

    // The environment can be reset, this too would be reset and would re-parse
    // the hosts again. Save some time during the same environment instance.
    static $hosts;

    // Parse the whitelist of internal hosts.
    if (!isset($hosts)) {
      $hosts = preg_split("/\r\n|\n/", $this->getSetting('internal_host_whitelist'), -1, PREG_SPLIT_NO_EMPTY);

      // Ensure that the site's base url host name is always in this whitelist.
      $base_host = parse_url($GLOBALS['base_url'], PHP_URL_HOST);
      $key = array_search($base_host, $hosts);
      if ($key === FALSE) {
        $hosts[] = $base_host;
      }
    }

    // Iterate through the internal host whitelist.
    $internal = FALSE;
    foreach ($hosts as $host) {
      if ($host === $url_host) {
        $internal = TRUE;
        break;
      }
    }

    return !$internal;
  }

}
