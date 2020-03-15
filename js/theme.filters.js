(function ($, d) {

    $.smartfiltersTheme = function(_filters, _options){
        var that = this;
        that.filters = _filters;
        that.o = $.extend({
            parentLabelSelector: 'label',
            parentParamSelector: '.filter-param,p'
        }, _options);
        that.$form = findForm();
        if(that.$form) {
            setTimeout(function() {
                disableFilters();
                bindAjax();
            }, 1000);
        } else {
            $.smartfiltersTheme.log('$.smartfiltersTheme fail: form not found');
        }

        function findForm () {
            var $f = false;
            $.each(that.filters, function (name,v) {
                if(!$f || !$f.length) {
                    $f = $('[name^="'+name+'"]').closest('form');
                    return false;
                }
            });
            return $f && $f.length ? $f : false;
        }
        
        function disableFilters() {
            var $fields, $field, a, disabled, i, $param, $min, $max, $slider, values, value;
            $.each(that.filters, function (name,v) {
                $fields = that.$form.find('input[name="' + name + '[]"]');
                if ($fields.length)
                {
                    $param = $fields.closest(that.o.parentParamSelector);
                    if(v.disabled && !$.isEmptyObject(v.disabled)) {

                        $fields.each(function (index, f) {
                            $field = $(f);
                            i = $field.val();

                            disabled =
                                (v.disabled.hasOwnProperty(i) && v.disabled[i]) ||
                                !v.values.hasOwnProperty(i)
                            ;

                            $field.prop('disabled', disabled).trigger('refresh');
                            a = disabled ? 'add' : 'remove';
                            $field.closest(that.o.parentLabelSelector)[a+'Class']('sf-label-disabled');
                        });

                        a = $fields.filter(':not(:disabled)').length ? 'remove' : 'add';
                        $param[a+'Class']('sf-param-disabled');
                    } else {
                        $fields.prop('disabled', false).trigger('refresh');
                        $fields.closest(that.o.parentLabelSelector).removeClass('sf-label-disabled');
                        $param.removeClass('sf-param-disabled');
                    }
                }
                else if(v.hasOwnProperty('nmin') || v.hasOwnProperty('nmax'))
                {
                    $min = that.$form.find('input[name="' + name + (name !== 'price' ? '[min]' : '_min') + '"]');
                    $max = that.$form.find('input[name="' + name + (name !== 'price' ? '[max]' : '_max') + '"]');

                    if(v.hasOwnProperty('nmin') && v.hasOwnProperty('nmax')) {
                        v.nmin = Math.floor(v.nmin);
                        v.nmax = Math.ceil(v.nmax);

                        $param = $max.closest(that.o.parentParamSelector);

                        /* jQuery UI */
                        $slider = $param.find('.ui-slider');

                        if(name === 'price') {
                            log($param);
                            log('$slider.length ' + $slider.length);
                        }
                        if ($slider.length) {
                            try {
                                values = $slider.slider('option', 'values');
                                $min.prop('placeholder', v.nmin);
                                $max.prop('placeholder', v.nmax);

                                value = parseFloat($min.val());
                                if(value.toString() !== 'NaN') {
                                    if(value > v.nmax) {
                                        values[0] = v.nmax;
                                        $min.val(values[0]);
                                    } else {
                                        values[0] = value;
                                    }
                                } else {
                                    $max.val('');
                                    values[0] = v.nmin;
                                }

                                value = parseFloat($max.val());
                                if(value.toString() !== 'NaN') {
                                    if(value < v.nmin) {
                                        values[1] = v.nmin;
                                        $max.val(values[1]);
                                    } else {
                                        values[1] = value;
                                    }
                                } else {
                                    $max.val('');
                                    values[1] = v.nmax;
                                }
                                $slider.slider('option', 'values', values);
                            } catch (ex) {
                                log(ex);
                            }
                        }
                    }

                    /* search selects */
                }
                else if(($fields = that.$form.find('select[name="' + name + '[]"]')) && $fields.length)
                {
                    $param = $fields.closest(that.o.parentParamSelector);

                    $fields = $fields.find('option');
                    if(v.disabled && !$.isEmptyObject(v.disabled)) {

                        $fields.each(function (index, f) {
                            $field = $(f);
                            i = $field.prop('value');
                            console.log(i);
                            disabled =
                                (v.disabled.hasOwnProperty(i) && v.disabled[i]) ||
                                !v.values.hasOwnProperty(i)
                            ;

                            $field.prop('disabled', disabled).trigger('refresh');
                            //a = disabled ? 'add' : 'remove';
                            //$field.closest(that.o.parentLabelSelector)[a+'Class']('sf-label-disabled');
                        });

                        a = $fields.filter(':not(:disabled)').length ? 'remove' : 'add';
                        $param[a+'Class']('sf-param-disabled');
                    } else {
                        $fields.prop('disabled', false).trigger('refresh');
                        //$fields.closest(that.o.parentLabelSelector).removeClass('sf-label-disabled');
                        $param.removeClass('sf-param-disabled');
                    }
                    $fields.closest('select').trigger('refresh');
                }
            });
        }

        function bindAjax() {
            if(!$(d).data('smartfilters-event')) {
                $(d).data('smartfilters-event', function (e, r, settings) {
					/* @todo check r content type??? don't parse JS... or match "data-smartfilters-data" */
                    if(!/data-smartfilters-data/.test(r.responseText)) {
                        return;
                    }

                    var $d = $('<div>').html(r.responseText),
                        f = $d.find('[data-smartfilters-data]:first');

                    if(f.length) {
                        f = f.text();
                        f = $.parseJSON(f);
                        that.filters = f;
                        $.smartfiltersTheme(f, that.o);
                    }
                })
            }
            $(d).unbind('ajaxSuccess', $(d).data('smartfilters-event'));
            $(d).bind('ajaxSuccess', $(d).data('smartfilters-event'));
        }

        function log(str) {
            window.console && console.log('$.smartfiltersTheme:', str);
        }

    };

})(jQuery, document);