(function ($, Drupal, debounce) {
  Drupal.behaviors.extendedBlockManagerFilter = {
    attach: function attach(context, settings) {
      var $input = $('[name="block_filter"]').once('block_filter');
      var $table = $('#blocks');

      function filterRows(e) {
        var query = $(e.target).val().toLowerCase();

        if (query.length >= 2) {
          // Select 4 first td to match.
          $table.find('tr.draggable').each(function ($index, row) {
            if ($(row).find('td:lt(4)').text().toLowerCase().indexOf(query) === -1) {
              row.classList.add('filtered-out');
            }
          });
        } else {
          $table.find('tr.filtered-out').removeClass('filtered-out');
        }
      }

      if ($table.length) {
        $input.on('keyup', debounce(filterRows, 200));
      }
    }
  };
})(jQuery, Drupal, Drupal.debounce);
