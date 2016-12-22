(function ($) {
$.fn.smartfilters = function(options){
    var filter_change_timeoutId,
        settings = $.extend({
            content_selector : '#smartfiltercontent',
            product_list_selector : '#product-list',
            afterFilter : function () {

            }
        }, options),
        $sf = this;

    $('input,select', $sf).change(function() {
        if(filter_change_timeoutId) clearTimeout(filter_change_timeoutId);
        filter_change_timeoutId = setTimeout(function () {
            $sf.find('form').trigger('submit');
        }, 500)
    });

    $('form', $sf).submit(function (e) {
        e.preventDefault();
        $(settings.product_list_selector).html('<div class="smartfilter_loader"/>');
        var url = $(this).attr('action'),
            data = $(this).serialize();

        $.ajax({
            url: url,
            data: data,
            dataType: 'html',
            complete : function(){
            },
            success: function(response){
                $(settings.content_selector).html(response);
                
                // @todo доработать popState, тогда можно запускать
                //window.history.pushState(data, document.title, this.url);

                if(typeof settings.afterFilter === 'function') {
                    settings.afterFilter();
                }
            },
            fail: function () {
                window.location = this.url;
            }
        });
    });

    $('input[type="submit"]', $sf).hide();

    if($.ui && $.ui.slider) {
        $('.slider', $sf).each(function () {
            if (!$(this).find('.filter-slider').length) {
                $(this).append('<div class="filter-slider"></div>');
            } else {
                return;
            }

            var min = $(this).find('.min'),
                max = $(this).find('.max'),
                min_value = parseFloat(min.attr('placeholder')),
                max_value = parseFloat(max.attr('placeholder')),
                step = 1,
                slider = $(this).find('.filter-slider');

            if (slider.data('step')) {
                step = parseFloat(slider.data('step'));
            } else {
                var diff = max_value - min_value;

                step = diff / 10;
                var tmp = 0;
                while (step < 1) {
                    step *= 10;
                    tmp += 1;
                }
                step = Math.pow(10, -tmp);
                tmp = Math.round(100000 * Math.abs(Math.round(min_value) - min_value)) / 100000;
                if (tmp && tmp < step) {
                    step = tmp;
                }
                tmp = Math.round(100000 * Math.abs(Math.round(max_value) - max_value)) / 100000;
                if (tmp && tmp < step) {
                    step = tmp;
                }
            }

            slider.slider({
                range: true,
                min: min_value,
                max: max_value,
                step: step,
                values: [
                    parseFloat(min.val().length ? min.val() : min_value),
                    parseFloat(max.val().length ? max.val() : max_value)
                ],
                slide: function (event, ui) {
                    var v = ui.values[0] == $(this).slider('option', 'min') ? '' : ui.values[0];
                    min.val(v);
                    v = ui.values[1] == $(this).slider('option', 'max') ? '' : ui.values[1];
                    max.val(v);
                },
                stop: function (event, ui) {
                    min.change();
                }
            });
            min.add(max).change(function () {
                var v_min = min.val() === '' ? slider.slider('option', 'min') : parseFloat(min.val());
                var v_max = max.val() === '' ? slider.slider('option', 'max') : parseFloat(max.val());
                if (v_max >= v_min) {
                    slider.slider('option', 'values', [v_min, v_max]);
                }
            });
        });
    }
}
})(jQuery);