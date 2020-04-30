(function ($, Drupal) {
  Drupal.behaviors.markdownSummary = {
    attach: function attach(context, settings) {
      var $context = $(context);
      var $wrapper = $context.find('[data-markdown-element="wrapper"]');

      $wrapper.once('markdown-summary').each(function () {
        var $inputs = $(this).find(':input[data-markdown-element]');
        $inputs.each(function () {
          var $input = $(this);
          var elementType = $input.data('markdownElement');
          var $item = $input.closest('.js-vertical-tabs-pane,.vertical-tabs__pane');
          var verticalTab = $item.data('verticalTab');
          if (verticalTab) {
            $input.on('click.markdownSummary', function () {
              verticalTab.updateSummary();
            });

            verticalTab.details.drupalSetSummary(function () {
              var summary = [];
              switch (elementType) {
                case 'parser':
                  summary.push($input.children(':selected').text())
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
                    }
                    else if (bundle) {
                      variables['@bundle'] = bundle;
                      $label.html(Drupal.t('@label (required by: @bundle)', variables))
                      summary.push(Drupal.t('Required by: @bundle', variables));
                    }
                    else {
                      $label.html(originalLabel);
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
