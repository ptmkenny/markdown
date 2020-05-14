<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Url;
use Drupal\markdown\Plugin\Markdown\AllowedHtmlInterface;
use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use Drupal\markdown\Plugin\Markdown\CommonMark\RendererInterface;
use Drupal\markdown\Plugin\Markdown\ParserInterface;
use Drupal\markdown\Plugin\Markdown\SettingsInterface;
use Drupal\markdown\Traits\SettingsTrait;
use Drupal\markdown\Util\KeyValuePipeConverter;
use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\ElementRendererInterface;
use League\CommonMark\HtmlElement;
use League\CommonMark\Inline\Element\AbstractInline;
use League\CommonMark\Inline\Element\Link;
use League\CommonMark\Inline\Renderer\InlineRendererInterface;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension as LeagueExternalLinkExtension;

/**
 * @MarkdownExtension(
 *   id = "league/commonmark-ext-external-links",
 *   label = @Translation("External Links"),
 *   installed = "\League\CommonMark\Extension\ExternalLink\ExternalLinkExtension",
 *   description = @Translation("Automatically detect links to external sites and adjust the markup accordingly."),
 *   url = "https://commonmark.thephpleague.com/extensions/external-links/",
 * )
 * @MarkdownAllowedHtml(
 *   id = "league/commonmark-ext-external-links",
 *   label = @Translation("External Links"),
 *   installed = "\League\CommonMark\Extension\ExternalLink\ExternalLinkExtension",
 *   description = @Translation("Automatically detect links to external sites and adjust the markup accordingly."),
 * )
 */
class ExternalLinkExtension extends BaseExtension implements AllowedHtmlInterface, RendererInterface, InlineRendererInterface, SettingsInterface, PluginFormInterface {

  use SettingsTrait {
    getConfiguration as getConfigurationTrait;
    getSettings as getSettingsTrait;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'html_class' => '',
      'internal_hosts' => [
        '[current-request:host]',
      ],
      'nofollow' => '',
      'noopener' => 'external',
      'noreferrer' => 'external',
      'open_in_new_window' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function allowedHtmlTags(ParserInterface $parser, ActiveTheme $activeTheme = NULL) {
    return [
      'a' => [
        'href' => TRUE,
        'hreflang' => TRUE,
        'rel' => [
          'nofollow' => TRUE,
          'noopener' => TRUE,
          'noreferrer' => TRUE,
        ],
        'target' => [
          '_blank' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\markdown\Form\SubformStateInterface $form_state */

    $element += $this->createSettingElement('internal_hosts', [
      '#type' => 'textarea',
      '#description' => $this->t('Defines a whitelist of hosts which are considered non-external and should not receive the external link treatment. This can be a single host name, like <code>example.com</code>, which must match exactly. Wildcard matching is also supported using regular expression like <code>/(^|\.)example\.com$/</code>. Note that you must use <code>/</code> characters to delimit your regex. By default, if no internal hosts are provided, all links will be considered external. One host per line.'),
    ], $form_state, '\Drupal\markdown\Util\KeyValuePipeConverter::denormalizeNoKeys');

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $element['token'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [],
        '#global_types' => TRUE,
        '#dialog' => TRUE,
      ];
    }
    else {
      $element['token']['#markup'] = t('To browse available tokens, install the @token module.', [
        '@token' => \Drupal\Core\Link::fromTextAndUrl('Token', Url::fromUri('https://www.drupal.org/project/token', ['attributes' => ['target' => '_blank']]))->toString(),
      ]);
    }

    $element += $this->createSettingElement('html_class', [
      '#type' => 'textfield',
      '#title' => $this->t('HTML Class'),
      '#description' => $this->t('An HTML class that should be added to external link <code>&lt;a&gt;</code> tags.'),
    ], $form_state);

    $element += $this->createSettingElement('open_in_new_window', [
      '#type' => 'checkbox',
      '#description' => $this->t('Adds <code>target="_blank"</code> to external link <code>&lt;a&gt;</code> tags.'),
    ], $form_state);

    $relOptions = [
      '' => $this->t('No links'),
      'all' => $this->t('All links'),
      'external' => $this->t('External links only'),
      'internal' => $this->t('Internal links only'),
    ];

    $element += $this->createSettingElement('nofollow', [
      '#type' => 'select',
      '#title' => $this->t('No Follow'),
      '#description' => $this->t('Sets the "nofollow" value in the <code>rel</code> attribute. This value instructs search engines to not influence the ranking of the link\'s target in the search engine\'s index. Using this can negatively impact your site\'s SEO ranking if done improperly.'),
      '#options' => $relOptions,
    ], $form_state);

    $element += $this->createSettingElement('noopener', [
      '#type' => 'select',
      '#title' => $this->t('No Opener'),
      '#description' => $this->t('Sets the "noopener" value in the <code>rel</code> attribute. This value instructs the browser to prevent the new page from being able to access the the window that opened the link and forces it run in a separate process.'),
      '#options' => $relOptions,
    ], $form_state);

    $element += $this->createSettingElement('noreferrer', [
      '#type' => 'select',
      '#title' => $this->t('No Referrer'),
      '#description' => $this->t('Sets the "noreferrer" value in the <code>rel</code> attribute. This value instructs the browser from sending an HTTP referrer header to ensure that no referrer information will be leaked.'),
      '#options' => $relOptions,
    ], $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = $this->getConfigurationTrait();

    // Normalize settings from a key|value string into an associative array.
    foreach (['internal_hosts'] as $name) {
      if (isset($configuration['settings'][$name])) {
        $configuration['settings'][$name] = KeyValuePipeConverter::normalize($configuration['settings'][$name]);
      }
    }

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings($runtime = FALSE) {
    $settings = $this->getSettingsTrait($runtime);

    if (!$runtime) {
      return $settings;
    }

    $token = \Drupal::token();
    foreach ($settings['internal_hosts'] as &$host) {
      $host = $token->replace($host);
    }
    $settings['internal_hosts'] = array_unique($settings['internal_hosts']);

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function register(ConfigurableEnvironmentInterface $environment) {
    $environment->addExtension(new LeagueExternalLinkExtension());
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
  public function render(AbstractInline $inline, ElementRendererInterface $htmlRenderer) {
    if (!($inline instanceof Link)) {
      throw new \InvalidArgumentException('Incompatible inline type: ' . get_class($inline));
    }

    $attributes = $inline->getData('attributes', []);
    $external = $inline->getData('external');
    $attributes['href'] = $inline->getUrl();

    // Determine which rel attributes to set.
    $rel = [];
    foreach (['nofollow', 'noopener', 'noreferrer'] as $type) {
      $value = $this->getSetting($type);
      if ($value === 'all' || ($external && $value === 'external') || (!$external && $value === 'internal')) {
        $rel[] = $type;
      }
    }

    // Set the rel attribute.
    if ($rel) {
      $attributes['rel'] = implode(' ', $rel);
    }
    // Otherwise, unset whatever CommonMark set from the extension.
    else {
      unset($attributes['rel']);
    }

    return new HtmlElement('a', $attributes, $htmlRenderer->renderInlines($inline->children()));
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Normalize settings from a key|value string into an associative array.
    foreach (['internal_hosts'] as $name) {
      if (isset($configuration['settings'][$name])) {
        $configuration['settings'][$name] = KeyValuePipeConverter::normalize($configuration['settings'][$name]);
      }
    }
    return parent::setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsKey() {
    return 'external_link';
  }

}
