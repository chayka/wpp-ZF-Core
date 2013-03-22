jQuery(function($){

    $.datepicker.regional[ "ru" ] = {
        "closeText":"Готово",
        "prevText":"Пред.",
        "nextText":"След.",
        "currentText":"Сегодня",
        "monthNames":[
            "Январь", "Февраль",
            "Март", "Апрель", "Май",
            "Июнь", "Июль", "Август",
            "Сентябрь", "Октябрь", "Ноябрь",
            "Декабрь"],
        "monthNamesShort":[
            "Янв","Фев",
            "Мар","Апр","Май",
            "Июн","Июл","Авг",
            "Сен","Окт","Ноя",
            "Дек"],
        "dayNames":[
            "Воскресенье","Понедельник","Вторник",
            "Среда","Четверг","Пятница","Суббота"],
        "dayNamesShort":[
            "Вс","Пн","Вт",
            "Ср","Чт","Пт",
            "Сб"],
        "dayNamesMin":[
            "Вс","Пн","Вт",
            "Ср","Чт","Пт",
            "Сб"],
        "weekHeader":"Wk",
        "dateFormat":"dd.mm.yy",
        "firstDay":1,
        "isRTL":false,
        "showMonthAfterYear":false,
        "yearSuffix":""
    };
    $.datepicker.setDefaults( $.datepicker.regional[ "ru" ] );            
});