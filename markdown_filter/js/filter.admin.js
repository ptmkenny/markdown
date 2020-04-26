/**
 * @file
 * Markdown filter admin JavaScript.
 */

(function ($, Drupal) {
  'use strict';

  var $document = $(document);

  // Duplicate the "click" event from filter module's filter.admin.js but
  // for the "checked" States API event.
  $document.on('state:checked', function (e) {
    if (e.target) {
      var $checkbox = $(e.target);

      // Only process checkboxes in the status wrapper.
      if (!$checkbox.parents('#filters-status-wrapper')[0]) {
        return;
      }

      // Retrieve the tabledrag row belonging to this filter.
      var $row = $('#' + $checkbox.attr('id').replace(/-status$/, '-weight'), context).closest('tr');
      // Retrieve the vertical tab belonging to this filter.
      var tab = $('#' + $checkbox.attr('id').replace(/-status$/, '-settings'), context).data('verticalTab');
      if ($checkbox.is(':checked')) {
        $row.show();
        if (tab) {
          tab.tabShow().updateSummary();
        }
      }
      else {
        $row.hide();
        if (tab) {
          tab.tabHide().updateSummary();
        }
      }
      // Re-stripe table after toggling visibility of table row.
      Drupal.tableDrag['filter-order'].restripeTable();

      // Attach summary for configurable filters (only for screen-readers).
      if (tab) {
        tab.fieldset.drupalSetSummary(function () {
          return $checkbox.is(':checked') ? Drupal.t('Enabled') : Drupal.t('Disabled');
        });
      }
    }
  });

})(window.jQuery, window.Drupal);
