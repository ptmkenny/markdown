<?php

namespace Drupal\markdown\Plugin\Markdown\CommonMark\Extension;

use Drupal\markdown\Plugin\Markdown\CommonMark\BaseExtension;
use League\CommonMark\ConfigurableEnvironmentInterface;
use Webuni\CommonMark\AttributesExtension\AttributesExtension as WebuniAttributesExtension;

/**
 * @MarkdownExtension(
 *   id = "webuni/commonmark-attributes-extension",
 *   installed = "\Webuni\CommonMark\AttributesExtension\AttributesExtension",
 *   label = @Translation("Attributes"),
 *   description = @Translation("Adds a syntax to define attributes on the various HTML elements in markdown’s output."),
 *   url = "https://github.com/webuni/commonmark-attributes-extension",
 * )
 */
class AttributesExtension extends BaseExtension {

  /**
   * {@inheritdoc}
   */
  public function register(ConfigurableEnvironmentInterface $environment) {
    $environment->addExtension(new WebuniAttributesExtension());
  }

}
