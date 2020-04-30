<?php

namespace Drupal\markdown\Plugin\Markdown;

use Drupal\Core\Plugin\ObjectWithPluginCollectionInterface;

/**
 * Interface MarkdownInterface.
 */
interface ExtensibleMarkdownParserInterface extends MarkdownParserInterface, MarkdownGuidelinesAlterInterface, ObjectWithPluginCollectionInterface {

  /**
   * An array of extension interfaces that the parser supports.
   *
   * @return string[]
   */
  public function extensionInterfaces();

  /**
   * Retrieves plugin identifiers of extensions bundled with the parser.
   *
   * @return string[]
   */
  public function getBundledExtensionIds();

  /**
   * Retrieves a specific extension plugin instance.
   *
   * @param string $extensionId
   *   The identifier of the extension plugin instance to return.
   *
   * @return \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface|null
   */
  public function extension($extensionId);

  /**
   * Returns the ordered collection of extension plugin instances.
   *
   * @return \Drupal\markdown\MarkdownExtensionPluginCollection
   *   The extension plugin collection.
   */
  public function extensions();

}
