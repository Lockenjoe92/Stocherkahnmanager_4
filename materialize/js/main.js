$(document).ready(function(){

    $('.datepicker').datepicker({
        autoClose: true,
        yearRange: [1900,2022],
        setDefaultDate: true,
        i18n: {
            today: 'Heute',
            cancel: 'Schließen',
            clear: 'Zurücksetzen',
            done: 'OK',
            nextMonth: 'Nächster Monat',
            previousMonth: 'Vorheriger Monat',
            months: [ 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember' ],
            monthsShort: [ 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez' ],
            weekdays: [ 'Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag' ],
            weekdaysShort: [ 'So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa' ],
            weekdaysAbbrev: [ 'S', 'M', 'D', 'M', 'D', 'F', 'S' ],
        },
        format: 'yyyy-mm-dd',
        firstDay: 1
    });

    $('.datepicker_inkl_next_year').datepicker({
        autoClose: true,
        yearRange: [1900,2030],
        setDefaultDate: true,
        i18n: {
            today: 'Heute',
            cancel: 'Schließen',
            clear: 'Zurücksetzen',
            done: 'OK',
            nextMonth: 'Nächster Monat',
            previousMonth: 'Vorheriger Monat',
            months: [ 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember' ],
            monthsShort: [ 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez' ],
            weekdays: [ 'Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag' ],
            weekdaysShort: [ 'So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa' ],
            weekdaysAbbrev: [ 'S', 'M', 'D', 'M', 'D', 'F', 'S' ],
        },
        format: 'yyyy-mm-dd',
        firstDay: 1
    });

    $('.datepicker_new_res').datepicker({
        autoClose: true,
        yearRange: [2020,2022],
        setDefaultDate: true,
        i18n: {
            today: 'Heute',
            cancel: 'Schließen',
            clear: 'Zurücksetzen',
            done: 'OK',
            nextMonth: 'Nächster Monat',
            previousMonth: 'Vorheriger Monat',
            months: [ 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember' ],
            monthsShort: [ 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez' ],
            weekdays: [ 'Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag' ],
            weekdaysShort: [ 'So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa' ],
            weekdaysAbbrev: [ 'S', 'M', 'D', 'M', 'D', 'F', 'S' ],
        },
        format: 'yyyy-mm-dd',
        firstDay: 1
    });
  
  $('.timepicker').timepicker({
    twelveHour: false,
    i18n: {
      cancel: 'Schließen',
	    done: 'OK'
	  }
  });
});
