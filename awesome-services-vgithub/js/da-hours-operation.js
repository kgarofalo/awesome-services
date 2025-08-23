jQuery(function ($) {
  const sample = $('input[name$="_open_hour"]').first().attr('name') || '';
  const prefix = sample.replace(/(sun|mon|tue|wed|thu|fri|sat)_open_hour$/, '');

  const dayPrefix = {
    sunday: 'sun',
    monday: 'mon',
    tuesday: 'tue',
    wednesday: 'wed',
    thursday: 'thu',
    friday: 'fri',
    saturday: 'sat',
  };

  const days = Object.keys(dayPrefix);

  function normalize(val) {
    if (!val) return '';
    const [h, m] = val.split(':');
    return h.padStart(2, '0') + ':' + (m || '00');
  }

  function sameHours(day1, day2) {
    const p1 = dayPrefix[day1], p2 = dayPrefix[day2];
    const o1 = normalize($(`[name="${prefix}${p1}_open_hour"]`).val());
    const c1 = normalize($(`[name="${prefix}${p1}_close_hour"]`).val());
    const o2 = normalize($(`[name="${prefix}${p2}_open_hour"]`).val());
    const c2 = normalize($(`[name="${prefix}${p2}_close_hour"]`).val());
    return o1 === o2 && c1 === c2;
  }

  function isDayOpen(day) {
    const toggle = $(`[name="${prefix}open_${day}"]`);
    return toggle.filter(':checked').val() === "1"; // "1" means open, "0" means closed
  }

  function refreshBtn(day) {
    if (day === 'sunday') return; // Skip Sunday as no previous day exists

    const prev = days[days.indexOf(day) - 1];
    const p1 = dayPrefix[prev], p2 = dayPrefix[day];
    const button = $(`.${day}-apply-prev`); // The button to apply previous day's hours

    const open1 = $(`[name="${prefix}${p1}_open_hour"]`).val();
    const close1 = $(`[name="${prefix}${p1}_close_hour"]`).val();
    const open2 = $(`[name="${prefix}${p2}_open_hour"]`).val();
    const close2 = $(`[name="${prefix}${p2}_close_hour"]`).val();

    // Check if both open and close hours are set for both days
    const allSet = open1 && close1 && open2 && close2;
    const shouldShow = isDayOpen(day) && allSet && !sameHours(day, prev);

    button.toggleClass('visible', shouldShow).toggleClass('hidden', !shouldShow);
  }

  // Run on load + on relevant input changes
  days.forEach((day, i) => {
    if (day !== 'sunday') refreshBtn(day);

    const p = dayPrefix[day];
    const selector = `[name="${prefix}${p}_open_hour"], [name="${prefix}${p}_close_hour"], [name="${prefix}open_${day}"]`;

    $(document).on('input change', selector, () => {
      refreshBtn(day);
      if (i + 1 < days.length) refreshBtn(days[i + 1]); // Also refresh the next day
    });
  });

  // "Apply Prev" button logic
  $(document).on('click', '.apply-prev-day-times', function () {
    const day = this.id.replace('_apply_prev', '');
    const i = days.indexOf(day);
    const prev = days[i - 1];
    const p1 = dayPrefix[prev], p2 = dayPrefix[day];

    // Apply previous day's hours to the current day
    $(`[name="${prefix}${p2}_open_hour"]`).val($(`[name="${prefix}${p1}_open_hour"]`).val());
    $(`[name="${prefix}${p2}_close_hour"]`).val($(`[name="${prefix}${p1}_close_hour"]`).val());

    // Refresh the button visibility
    refreshBtn(day);
    if (i + 1 < days.length) refreshBtn(days[i + 1]); // Also refresh the next day
  });
});

