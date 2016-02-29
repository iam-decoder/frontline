(function ($, window, undefined)
{
    window.FES = {
        singleton: {},
        isStringValidJson: function (str)
        {
            if (typeof str !== "string" || str.length < 2) {
                return false;
            }
            return /^[\],:{}\s]*$/.test(str.replace(/\\["\\\/bfnrtu]/g, '@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').replace(/(?:^|:|,)(?:\s*\[)+/g, ''));
        },
        getMeta: function (selector)
        {
            var $meta_node = $(selector);
            if ($meta_node.length > 0) {

                var rsa_data = $meta_node.data('cookieString');
                if (rsa_data.hasOwnProperty('n') && rsa_data.hasOwnProperty('e')) {
                    window.FES.singleton.encryption = new window.FES.Encryption();
                    window.FES.singleton.encryption.setPublic(rsa_data['n'], rsa_data['e']);
                }

                window.FES.base_url = $meta_node.data('baseUrl');
                $meta_node.remove();
            }
        },
        generateDataTable: function (data)
        {
            console.log("generateDataTable: ", data);
        },
        contentTransition: function (target, html, duration)
        {
            if (!target || !html) {
                return false;
            }
            var
                $body = $(target),
                $new_content = $(html).css("display", "none"),
                transition_duration = (duration || 800) / 2;
            if ($body.length > 0) {
                $body.find("> *").each(function (i, el)
                {
                    $(el).fadeOut(transition_duration, function ()
                    {

                        $body.empty();
                        $body.append($new_content);
                        $new_content.fadeIn(transition_duration, function ()
                        {
                            $(document).trigger("content_transition_end");
                        });
                    });
                });
            } else {
                console.warn("could not find " + target + " in the DOM...");
            }
        },
        onFormSubmit: function (e)
        {
            e.preventDefault();
            var $form = $(this);
            var form_data = $form.serializeObject();
            if (form_data && typeof form_data.password === "string" && form_data.password.length > 0) {
                form_data.password = window.FES.singleton.encryption.encrypt(form_data.password);
            }

            $.ajax({
                type: $form.attr('method'),
                async: true,
                url: $form.attr('action'),
                data: form_data,
                dataType: "html",
                crossDomain: true,
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                xhrFields: {
                    withCredentials: true
                },
                success: function (html)
                {
                    window.FES.contentTransition('body', html, 600);
                },
                error: function (data)
                {
                    if (Object.prototype.toString.call(data) === "[object String]") {
                        if (data.indexOf("{") === 0) {
                            data = JSON.parse(data);
                            if (data.errors) {
                                $.each(data.errors, function (i, e)
                                {
                                    console.log(e);
                                });
                            }
                        }
                    } else {
                        console.log(data);
                    }
                }
            });
            return false; //stop the click propagation
        },
        onDataLoadClick: function (e)
        {
            e.preventDefault();

            var $source = $(this);
            var table = $source.data("load");

            if (typeof table !== "string" || table.length <= 0) { //table type is required
                return false;
            }

            var withData = $source.data("with");
            var ajax_data = {
                table: table
            };
            if (window.FES.isStringValidJson(withData)) {
                ajax_data = $.extend(ajax_data, JSON.parse(withData));
            } else
                if (typeof withData === "string") {
                    ajax_data.data = withData;
                }

            $.ajax({
                type: "GET",
                async: true,
                url: window.FES.base_url + "/tabledata",
                data: ajax_data,
                crossDomain: true,
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                xhrFields: {
                    withCredentials: true
                },
                success: function (response)
                {
                    var html = "";

                    if (window.FES.isStringValidJson(response)) {
                        html = window.FES.generateDataTable(JSON.parse(response));
                    } else {
                        html = response;
                    }

                    window.FES.contentTransition("#datatable", html, 400);
                },
                error: function (response)
                {
                    console.log("error received: ", response);
                }
            });

            return false;
        }
    };
})(jQuery, window);