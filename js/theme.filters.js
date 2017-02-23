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
                            a = disabled ? 'add' : 'remove';
                            $field.closest(that.o.parentLabelSelector)[a+'Class']('sf-label-disabled');
                        }

                        a = $fields.filter(':not(:disabled)').length ? 'remove' : 'add';
                        $fields.closest(that.o.parentParamSelector)[a+'Class']('sf-param-disabled');
                    } else {
                        $fields.prop('disabled', false);
                        $fields.closest(that.o.parentLabelSelector).removeClass('sf-label-disabled');
                        $fields.closest(that.o.parentParamSelector).removeClass('sf-param-disabled');
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
                        $.smartfiltersTheme(f, that.o);
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