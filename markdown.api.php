<?php

/**
 * @file
 * Hooks and alters provided by the Markdown module.
 */

/**
 * Allows modules to alter the list of incompatible filters.
 *
 * @param array $compatibleFilters
 *   An associative array of compatible filters, where the key is the filter
 *   identifier and the value is a boolean: TRUE if compatible, FALSE otherwise.
 */
function hook_markdown_compatible_filters_alter(array &$compatibleFilters) {
  // Re-enable the HTML Corrector filter as compatible.
  $compatibleFilters['filter_htmlcorrector'] = TRUE;
}
