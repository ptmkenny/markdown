<?php

namespace Drupal\markdown;

use Drupal\filter\Plugin\Filter\FilterHtml;

class MarkdownFilterHtml extends FilterHtml {

  /**
   * {@inheritdoc}
   */
  public function getHTMLRestrictions() {
    $restrictions = parent::getHTMLRestrictions();


    // Allow settings to provide more permissive global attributes.
    $star_protector = '__zqh6vxfbk3cg__';
    if (isset($restrictions['allowed'][$star_protector])) {
      $restrictions['allowed']['*'] += $restrictions['allowed'][$star_protector];
    }

    return $restrictions;
  }

}
