jQuery(function ($) {
  const pairs = {
    tue_apply_prev: ['mon_open_hour', 'mon_close_hour', 'tue_open_hour', 'tue_close_hour'],
    wed_apply_prev: ['tue_open_hour', 'tue_close_hour', 'wed_open_hour', 'wed_close_hour'],
    thu_apply_prev: ['wed_open_hour', 'wed_close_hour', 'thu_open_hour', 'thu_close_hour'],
    fri_apply_prev: ['thu_open_hour', 'thu_close_hour', 'fri_open_hour', 'fri_close_hour'],
    sat_apply_prev: ['fri_open_hour', 'fri_close_hour', 'sat_open_hour', 'sat_close_hour'],
    sun_apply_prev: ['sat_open_hour', 'sat_close_hour', 'sun_open_hour', 'sun_close_hour']
  };

  function syncButton(btn, prevOpen, prevClose, thisOpen, thisClose) {
    const $wrapper = $(`.dibraco-button[data-name="${btn}"]`);

    const prevO = $(`#${prevOpen}`).val();
    const prevC = $(`#${prevClose}`).val();
    const thisO = $(`#${thisOpen}`).val();
    const thisC = $(`#${thisClose}`).val();

    const same = prevO === thisO && prevC === thisC;

    // Respect the controlling system: only toggle if wrapper has "visible"
    if ($wrapper.hasClass('visible')) {
      $wrapper.toggle(!same);
    }
  }

  Object.entries(pairs).forEach(([btn, [prevOpen, prevClose, thisOpen, thisClose]]) => {
    // On click: copy previous day’s times into this day
    $(document).on('click', `#${btn}`, function (e) {
      e.preventDefault();
      $(`#${thisOpen}`).val($(`#${prevOpen}`).val()).trigger('change');
      $(`#${thisClose}`).val($(`#${prevClose}`).val()).trigger('change');
      syncButton(btn, prevOpen, prevClose, thisOpen, thisClose);
    });

    // Listen for any changes on the four relevant inputs
    $(document).on('input change', `#${prevOpen}, #${prevClose}, #${thisOpen}, #${thisClose}`, function () {
      syncButton(btn, prevOpen, prevClose, thisOpen, thisClose);
    });

    // Initial check
    syncButton(btn, prevOpen, prevClose, thisOpen, thisClose);
  });
});
