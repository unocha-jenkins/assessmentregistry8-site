(function ($, Drupal, debounce) {
  Drupal.behaviors.extendedBlockManagerFilter = {
    attach: function attach(context, settings) {
      var $input = $('[name="path_filter"]').once('path_filter');
      var $table = $('#blocks');

      function filterRows(e) {
        var query = $(e.target).val().toLowerCase();

        if (query.length >= 2) {
          // TODO: Use better class.
          $table.find('tr.draggable').each(function ($index, $row) {
            if ($row.innerText.toLowerCase().indexOf(query) === -1) {
              $row.classList.add('filtered-out');
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
