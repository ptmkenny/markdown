(function ($, Drupal) {
  var $document = $(document);


  // @todo Extract input history/dependents into its own library.
  var savePreviousInput = function (input) {
    var change = false;
    var $input = $(input);
    if ($input.is('[type="checkbox"]')) {
      if ($input.data('originalChecked') === void 0) {
        $input.data('originalChecked', $input.prop('checked'));
        change = true;
      }
    }
    else if ($input.data('originalValue') === void 0) {
      $input.data('originalValue', $input.val());
      change = true;
    }
    if ($input.data('originalDisabled') === void 0) {
      $input.data('originalDisabled', $input.prop('disabled'));
      change = true;
    }
    if (change) {
      $input.trigger('change');
    }
  }

  var restorePreviousInput = function (input) {
    var change = false;
    var $input = $(input);
    if ($input.is('[type="checkbox"]') && $input.data('originalChecked') !== void 0) {
      $input.prop('checked', $input.data('originalChecked'));
      $input.removeData('originalChecked');
      change = true;
    }
    else if ($input.data('originalValue') !== void 0) {
      $input.val('checked', $input.data('originalValue'));
      $input.removeData('originalValue');
      change = true;
    }
    if ($input.data('originalDisabled') !== void 0) {
      $input.prop('disabled', $input.data('originalDisabled'))
      $input.removeData('originalDisabled');
      change = true;
    }
    if (change) {
      $input.trigger('change');
    }
    return change;
  }

  $document
      .off('state:checked')
      .on('state:checked', function (e) {
        if (e.trigger && e.target) {
          var $target = $(e.target);
          var defaultValue = $target.data('markdownDefaultValue');

          // Act normally if there is not default value provided.
          if (defaultValue === void 0) {
            $target.prop('checked', e.value);
            return;
          }

          // Handle checked state so its default value is restored, not
          // automatically "checked" because its state says to.
          var states = $(e.target).data('drupalStates') || {};
          if ((states['!checked'] && e.value) || states['checked'] && !e.value) {
            if (!restorePreviousInput(e.target)) {
              $target.prop('checked', defaultValue);
            }
          }
          else {
            savePreviousInput(e.target);
            $target.prop('checked', e.value);
          }
        }
      });

  Drupal.behaviors.markdownSummary = {
    attach: function attach(context) {
      var $context = $(context);

      var $wrapper = $context.find('[data-markdown-element="wrapper"]');
      $wrapper.once('markdown-summary').each(function () {
        // Vertical tab summaries.
        var $inputs = $(this).find(':input[data-markdown-summary]');
        $inputs.each(function () {
          var $input = $(this);
          var summaryType = $input.data('markdownSummary');
          var $item = $input.closest('.js-vertical-tabs-pane,.vertical-tabs__pane');
          var verticalTab = $item.data('verticalTab');
          if (verticalTab) {
            $input.on('click.markdownSummary', function () {
              verticalTab.updateSummary();
            });

            verticalTab.details.drupalSetSummary(function () {
              var summary = [];
              switch (summaryType) {
                case 'parser':
                  summary.push($input.children(':selected').text())
                  break;

                case 'render_strategy':
                  var $selected = $input.children(':selected:first');
                  var renderStrategy = $selected.text();
                  if ($selected.val() === 'filter') {
                    var $allowedHtml = $item.find('[data-markdown-element="allowed_html"]');
                    var $reset = $item.find('[data-markdown-element="allowed_html_reset"]');
                    var defaultValue = allowedHtmlDefaultValue($reset);
                    if (defaultValue && $allowedHtml.val() !== defaultValue) {
                      renderStrategy += ' (' + Drupal.t('overridden') + ')';
                    }
                  }
                  summary.push(renderStrategy);
                  break;

                case 'extension':
                  var $parent = $input.parent();
                  var labelSelector = 'label[for="' + $input.attr('id') + '"]';
                  var $label = $parent.is(labelSelector) ? $parent : $parent.find(labelSelector);
                  if (!$label.data('original-label')) {
                    $label.data('original-label', $label.html());
                  }
                  var originalLabel = $label.data('original-label') || Drupal.t('Enable');
                  var variables = {'@label': originalLabel};

                  if (!$input.data('markdownInstalled')) {
                    $label.html(Drupal.t('@label (not installed)', variables))
                    summary.push(Drupal.t('Not Installed'))
                  }
                  else {
                    var bundle = $input.data('markdownBundle');
                    var requiredBy = [].concat($input.data('markdownRequiredBy')).map(function (id) {
                      var $dependent = $inputs.filter('[data-markdown-element="extension"][data-markdown-id="' + id + '"]');
                      if ($dependent[0]) {
                        return $dependent.is(':checked') ? $dependent.data('markdownLabel') : '';
                      }
                    }).filter(Boolean);
                    if (requiredBy.length) {
                      variables['@extensions'] = requiredBy.join(', ');
                      $label.html(Drupal.t('@label (required by: @extensions)', variables))
                      summary.push(Drupal.t('Required by: @extensions', variables));
                      savePreviousInput($input);
                      $input.prop('checked', true);
                      $input.prop('disabled', true);
                    }
                    else if (bundle) {
                      variables['@bundle'] = bundle;
                      $label.html(Drupal.t('@label (required by: @bundle)', variables))
                      summary.push(Drupal.t('Required by: @bundle', variables));
                      savePreviousInput($input);
                      $input.prop('checked', true);
                      $input.prop('disabled', true);
                    }
                    else {
                      $label.html(originalLabel);
                      restorePreviousInput($input);
                      summary.push($input.is(':checked') ? Drupal.t('Enabled') : Drupal.t('Disabled'));
                    }

                    // Trigger requirement summary updates.
                    [].concat($input.data('markdownRequires')).map(function (id) {
                      var $requirement = $inputs.filter('[data-markdown-element="extension"][data-markdown-id="' + id + '"]');
                      if ($requirement[0]) {
                        setTimeout(function () {
                          $requirement.triggerHandler('click.markdownSummary');
                          $requirement.trigger('change');
                        }, 10);
                      }
                    });
                  }
                  break;
              }
              return summary.join(', ');
            });

            verticalTab.updateSummary();
          }
        });
      });
    }
  };
})(jQuery, Drupal);
