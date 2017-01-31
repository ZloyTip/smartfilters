(function ($, d) {
    $.smartfiltersTheme = function(_filters, _options){
        var that = this;
        that.filters = _filters;
        that.options = $.extend({
            hideDisabled : false, /* hide disabled features */
            parentLabelSelector: 'label',
            parentParamSelector: '.filter-param,p'
        }, _options);
        that.$form = findForm();
        if(that.$form) {
            disableFilters();
            bindAjax();
        } else {
            log('$.smartfiltersTheme fail: form not found');
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
            var $fields, $field, a, disabled;
            $.each(that.filters, function (name,v) {
                $fields = that.$form.find('input[name="' + name + '[]"]');
                if ($fields.length) {
                    if(v.disabled && !$.isEmptyObject(v.disabled)) {
                        for(var i in v.disabled) {
                            $field = $fields.filter('[value="'+i+'"]');
                            disabled = v.disabled.hasOwnProperty(i) && v.disabled[i];
                            $field.prop('disabled', disabled);
                            if(that.options.hideDisabled) {
                                a = disabled ? 'hide' : 'show';
                                $field.closest(that.options.parentLabelSelector)[a]();
                            }
                        }
                        if(that.options.hideDisabled) {
                            a = $fields.filter(':visible').length ? 'show' : 'hide';
                            $fields.closest(that.options.parentParamSelector)[a]();
                        }
                    } else {
                        $fields.prop('disabled', false);
                        if(that.options.hideDisabled) {
                            $fields.closest(that.options.parentLabelSelector).show();
                            $fields.closest(that.options.parentParamSelector).show();
                        }
                    }
                } else {
                    /* search selects */
                }
            });
        }

        function bindAjax() {
            if(!$(d).data('smartfilters-event')) {
                $(d).data('smartfilters-event', function (e, r, settings) {
                    var $d = $('<div>').html(r.responseText),
                        f = $d.find('[data-smartfilters-data]:first');

                    if(f.length) {
                        f = f.text();
                        f = $.parseJSON(f);
                        that.filters = f;
                        $.smartfiltersTheme(f, that.options);
                    }
                })
            }
            $(d).unbind('ajaxSuccess', $(d).data('smartfilters-event'));
            $(d).bind('ajaxSuccess', $(d).data('smartfilters-event'));
        }

        function log(str) {
            window.console && console.log('$.smartfiltersTheme: '+str);
        }

    };

})(jQuery, document);