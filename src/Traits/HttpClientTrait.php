<?php

namespace Drupal\markdown\Traits;

/**
 * Trait to assist with creating an HTTP client using module info as user-agent.
 *
 * @todo Move upstream to https://www.drupal.org/project/installable_plugins.
 * @todo Re-visit this if/when https://www.drupal.org/project/plus is published
 *   or this kind of functionality is included in core somehow.
 */
trait HttpClientTrait {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected static $httpClient;

  /**
   * Retrieves an HTTP client.
   *
   * @param string $name
   *   A Drupal extension machine name.
   * @param string $type
   *   The type of Drupal extension, e.g. module or theme.
   *
   * @return \GuzzleHttp\Client
   *   An HTTP client.
   */
  protected static function httpClient($name = 'markdown', $type = 'module') {
    if (!static::$httpClient) {
      $extensionList = \Drupal::service("extension.list.$type");
      $info = $extensionList->getExtensionInfo($name);
      $extension = isset($info['name']) ? $info['name'] : $name;
      if ($info && !empty($info['version'])) {
        $extension .= '/' . $info['version'];
      }
      $extension .= " (+https://www.drupal.org/project/$name)";

      /** @var \Drupal\Core\Http\ClientFactory $httpClientFactory */
      $httpClientFactory = \Drupal::service('http_client_factory');
      static::$httpClient = $httpClientFactory->fromOptions([
        'headers' => [
          'User-Agent' => $extension . ' Drupal/' . \Drupal::VERSION . ' (+https://www.drupal.org/) ' . \GuzzleHttp\default_user_agent(),
        ],
      ]);
    }
    return static::$httpClient;
  }

}
