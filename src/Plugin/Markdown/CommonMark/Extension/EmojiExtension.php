<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\markdown\Plugin\Markdown\AllowedHtmlInterface;
use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use Drupal\markdown\Plugin\Markdown\ParserInterface;
use Drupal\markdown\Plugin\Markdown\SettingsInterface;
use Drupal\markdown\Traits\SettingsTrait;
use Drupal\markdown\Util\LaravelCacheRepositoryAdapter;
use Drupal\markdown\Util\LaravelCacheStoreAdapter;

/**
 * Emoji extension.
 *
 * @MarkdownAllowedHtml(
 *   id = "commonmark-emoji",
 * )
 * @MarkdownExtension(
 *   id = "commonmark-emoji",
 *   label = @Translation("Emoji"),
 *   description = @Translation("Adds emoji support to CommonMark."),
 *   libraries = {
 *     @ComposerPackage(
 *       id = "cachethq/emoji",
 *       experimental = @Translation("See <a href=':url' target='_blank'>thephpleague/commonmark#421</a>", arguments = {
 *         ":url" = "https://github.com/thephpleague/commonmark/issues/421",
 *       }),
 *       object = "\CachetHQ\Emoji\EmojiExtension",
 *       url = "https://github.com/CachetHQ/Emoji",
 *       requirements = {
 *          @InstallableRequirement(
 *             id = "parser:commonmark",
 *             callback = "::getVersion",
 *             constraints = {"Version" = ">=0.18.1 <1.0.0 || ^1.0"},
 *          ),
 *       },
 *     ),
 *   },
 * )
 */
class EmojiExtension extends BaseExtension implements AllowedHtmlInterface, PluginFormInterface, SettingsInterface {

  use SettingsTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings($pluginDefinition) {
    /* @var \Drupal\markdown\Annotation\InstallablePlugin $pluginDefinition */
    return [
      'github_api_token' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function allowedHtmlTags(ParserInterface $parser, ActiveTheme $activeTheme = NULL) {
    return [
      'img' => [
        'class' => 'emoji',
        'data-emoji' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function register($environment) {
    $token = $this->getSetting('github_api_token');

    // Immediately return if no token was provided.
    if (!$token) {
      return;
    }

    switch ($this->pluginDefinition->object) {
      case 'CachetHQ\\Emoji\\EmojiExtension':
        $repo = new \CachetHQ\Emoji\Repositories\GitHubRepository(\Drupal::httpClient(), $token);
        $cache = new LaravelCacheRepositoryAdapter(new LaravelCacheStoreAdapter(\Drupal::cache('markdown'), 'commonmark-ext-emoji'));
        $parser =  new \CachetHQ\Emoji\EmojiParser(new \CachetHQ\Emoji\Repositories\CachingRepository($repo, $cache, 'github-repo', 604800));
        $environment->addInlineParser($parser);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\markdown\Form\SubformStateInterface $form_state */

    $element += $this->createSettingElement('github_api_token', [
      '#type' => 'textfield',
      '#title' => $this->t('GitHub API Token'),
      '#description' => $this->t('You must <a href=":generate_token" target="_blank">generate a GitHub API token</a> that will be used as the map of available emojis. Note: it does not need any scopes or permissions, it is just used for authentication to avoid rate limiting.', [
        ':generate_token' => 'https://help.github.com/en/github/authenticating-to-github/creating-a-personal-access-token-for-the-command-line',
      ]),
    ], $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsKey() {
    return FALSE;
  }

}
