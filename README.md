This module provides Markdown integration for Drupal.

The Markdown syntax is designed to co-exist with HTML, so you can set
up input formats with both HTML and Markdown support. It is also meant
to be as human-readable as possible when left as "source".

There is current an issue open to make [CommonMark] the "official"
[Drupal Coding Standard].

While there are several types of PHP Markdown parsing libraries out
there, this module requires [league/commonmark] as the
default/fallback parser in a preemptive acceptance of the
[Drupal Coding Standard].

This module also supports additional PHP Markdown parsers for backwards
compatibility reasons and in an effort to open up other options, should
you desire a different solution:

- [erusev/parsedown] - `composer require erusev/parsedown`
- [michelf/php-markdown] - `composer require michelf/php-markdown`

## Try out a demonstration!

<https://markdown.unicorn.fail>

To see a full list of "long tips" provided by this filter, visit:

<https://markdown.unicorn.fail/filter/tips>

## Requirements

- **PHP >= 5.5.9** - This is the minimum PHP version for Drupal 8.0.0. Actual
  minimum PHP version depends on which parser you use.
  @todo this needs verification.

## [CommonMark] Extensions

- **Enhanced Links** - _Built in, enabled by default_  
    Extends [CommonMark] to provide additional enhancements when
    rendering links.
- **@ Autolinker** - _Built in, disabled by default_  
    Automatically link commonly used references that come after an
    at character (@) without having to use the link syntax.
- **# Autolinker** - _Built in, disabled by default_  
    Automatically link commonly used references that come after a hash
    character (#) without having to use the link syntax.
- **[webuni/commonmark-attributes-extension]**  
    Adds syntax to define attributes on various HTML elements inside a
    [CommonMark] markdown document.
- **[league/commonmark-extras]**  
    A collection of useful GFM extensions and utilities for the
    [league/commonmark] project.

## Programmatic Conversion

In some cases you may need to programmatically convert [CommonMark]
Markdown to HTML. This is especially true with support legacy
procedural/hook-based functions. An example of how to accomplish this
can be found in right here in this module:

```php
<?php

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\markdown\Markdown;

/**
 * Implements hook_help().
 *
 * {@inheritdoc}
 */
function markdown_help($route_name, RouteMatchInterface $route_match) {
  switch ($route_name) {
    case 'help.page.markdown':
      return Markdown::create()->loadPath(__DIR__ . '/README.md');
  }
}
```

If you need to parse Markdown in other services, inject it as a
dependency:

```php
<?php

use \Drupal\markdown\MarkdownInterface;

class MyService {

  /**
   * A MarkdownParser instance.
   *
   * @var \Drupal\markdown\Plugin\Markdown\MarkdownParserInterface
   */
  protected $markdown;

  /**
   * MyService constructor.
   *
   * @param \Drupal\markdown\MarkdownInterface $markdown
   *   The Markdown service.
   */
  public function __construct(MarkdownInterface $markdown) {
    $this->markdown = $markdown;
  }

  /**
   * MyService renderer.
   */
  public function render(array $items) {
    $build = ['#theme' => 'item_list', '#items' => []];
    foreach ($items as $markdown) {
      $build['#items'][] = $this->markdown->parse($markdown);
    }
    return $build;
  }
}
```

Or if using it in classes where modifying the constructor may prove
difficult, use the `MarkdownTrait`:

```php
<?php

use \Drupal\markdown\Traits\MarkdownTrait;

class MyController {

  use MarkdownTrait;

  /**
   * MyService renderer.
   */
  public function render(array $items) {
    $build = ['#theme' => 'item_list', '#items' => []];
    foreach ($items as $markdown) {
      $build['#items'][] = $this->markdown()->parse($markdown);
    }
    return $build;
  }

}
```

## Editor.md

If you are interested in a Markdown editor please check out the
[Editor.md] module for Drupal. The demonstration site for this module
also uses it if you want to take a peek!

## Notes

Markdown may conflict with other input filters, depending on the order
in which filters are configured to apply. If using Markdown produces
unexpected markup when configured with other filters, experimenting
with the order of those filters will likely resolve the issue.

Filters that should be run before Markdown filter includes:

- Code Filter
- GeSHI filter for code syntax highlighting

Filters that should be run after Markdown filter includes:

- Filter HTML
- Typogrify

The "Limit allowed HTML tags and correct faulty HTML" filter is a
special case:

For best security, ensure that it is run after the Markdown filter and
that only markup you would like to allow via HTML and/or Markdown is
configured to be allowed.

If you on the other hand want to make sure that all converted Markdown
text is preserved, run it before the Markdown filter. Note that
blockquoting with Markdown doesn't work in this case since
"Limit allowed HTML tags and correct faulty HTML" filter converts
`>` characters to `&gt;`.

[CommonMark]: http://commonmark.org/
[league/commonmark]: https://github.com/thephpleague/commonmark
[league/commonmark-extras]: https://github.com/thephpleague/commonmark-extras
[webuni/commonmark-attributes-extension]: https://github.com/webuni/commonmark-attributes-extension
[Drupal Coding Standard]: https://www.drupal.org/project/coding_standards/issues/2952616
[Editor.md]: https://drupal.org/project/editor_md
[erusev/parsedown]: https://github.com/erusev/parsedown
[michelf/php-markdown]: https://github.com/michelf/php-markdown
[The League of Extraordinary Packages]: https://commonmark.thephpleague.com/
