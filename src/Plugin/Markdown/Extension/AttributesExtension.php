<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use League\CommonMark\EnvironmentAwareInterface;
use League\CommonMark\EnvironmentInterface;
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
class AttributesExtension extends CommonMarkExtensionBase implements EnvironmentAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function setEnvironment(EnvironmentInterface $environment) {
    $environment->addExtension(new WebuniAttributesExtension());
  }

}
