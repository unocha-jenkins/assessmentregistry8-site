/**
 * @file
 * Table headings added as data-content to table cells for cards on mobile.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.cdResponsiveTable = {
    attach: function (context, settings) {
      // Table responsive
      $('.cd-table--responsive', context).once('cdResponsiveTable').each(function () {
        var head = $('th', this);
        var i = 0;
        $('td').each(function() {
          if (i >= head.length) {
            i = 0;
          }
          if ($.trim($(this).html()) !== '') {
            $(this).attr('data-content', $(head[i]).text());
          }
          i++;
        });
      });
    }
  };
})(jQuery, Drupal);
