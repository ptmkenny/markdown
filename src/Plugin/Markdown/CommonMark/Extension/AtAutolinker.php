<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use Drupal\markdown\Plugin\Markdown\SettingsInterface;
use Drupal\markdown\Traits\SettingsTrait;
use Drupal\user\Entity\User;
use League\CommonMark\Inline\Element\Link;
use League\CommonMark\Inline\Parser\InlineParserInterface;
use League\CommonMark\InlineParserContext;

/**
 * @MarkdownExtension(
 *   id = "at_autolinker",
 *   label = @Translation("@ Autolinker"),
 *   installed = TRUE,
 *   description = @Translation("Automatically link commonly used references that come after an at character (@) without having to use the link syntax."),
 * )
 */
class AtAutolinker extends BaseExtension implements InlineParserInterface, SettingsInterface, PluginFormInterface {

  use SettingsTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'type' => 'user',
      'format_username' => TRUE,
      'url' => 'https://www.drupal.org/u/[text]',
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
        'user' => $this->t('User'),
        'url' => $this->t('URL'),
      ],
    ], $form_state);

    $element += $this->createSettingElement('format_username', [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace username with formatted display name'),
      '#description' => $this->t('If enabled, it will replace the matched text with the formatted username.'),
    ], $form_state);
    $form_state->addElementState($element['format_username'], 'visible', 'type', ['value' => 'user']);

    $element += $this->createSettingElement('url', [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('A URL to format text with. Use the token "[text]" where it is needed. If you need to include the @, use the URL encoded equivalent: <code>%40</code>. Example: <code>https://twitter.com/search?q=%40[text]</code>.'),
    ], $form_state);
    $form_state->addElementState($element['url'], 'visible', 'type', ['value' => 'url']);

    return $element;
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
    return ['@'];
  }

  /**
   * {@inheritdoc}
   *
   * @noinspection PhpLanguageLevelInspection
   * @noinspection PhpInappropriateInheritDocUsageInspection
   */
  public function parse(InlineParserContext $inline_context): bool {
    $cursor = $inline_context->getCursor();

    // The @ symbol must not have any other characters immediately prior.
    $previous_char = $cursor->peek(-1);
    if ($previous_char !== NULL && $previous_char !== ' ') {
      // peek() doesn't modify the cursor, so no need to restore state first.
      return FALSE;
    }

    // Save the cursor state in case we need to rewind and bail.
    $previous_state = $cursor->saveState();

    // Advance past the @ symbol to keep parsing simpler.
    $cursor->advance();

    // Parse the handle.
    $text = $cursor->match('/^[^\s]+/');
    $url = '';
    $title = '';

    $type = $this->getSetting('type');
    if ($type === 'user') {
      $users = \Drupal::entityTypeManager()->getStorage('user');

      /** @var \Drupal\user\UserInterface $user */
      $user = is_numeric($text) ? $users->load($text) : $users->loadByProperties(['name' => $text]);
      if ($user && $user->id()) {
        $url = $user->toUrl('canonical', ['absolute' => TRUE])->toString();
        $title = $this->t('View user profile.');
        $text = $this->getSetting('format_username') ? $user->getDisplayName() : $user->getAccountName();
      }
      else {
        $text = FALSE;
      }
    }
    elseif ($type === 'url' && ($url = $this->getSetting('url')) && strpos($url, '[text]') !== FALSE) {
      $url = str_replace('[text]', $text, $url);
    }
    else {
      $text = FALSE;
    }

    // Regex failed to match; this isn't a valid @ handle.
    if (empty($text) || empty($url)) {
      $cursor->restoreState($previous_state);
      return FALSE;
    }

    $inline_context->getContainer()->appendChild(new Link($url, '@' . $text, $title));

    return TRUE;
  }

}
