(function ($, window, undefined)
{
    window.FES = {
        singleton: {},
        gaInitialized: false,
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

                window.FES.googleAnalyticsId = $meta_node.data("gaId");

                $meta_node.remove();
            }
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
                $body.html("<div class='stretch-block'>" + $body.html() + "</div>"); //wrapping in a div ensures that only 1 transition end will be triggered.
                $body.find("> div").each(function (i, el)
                {
                    $(el).fadeOut(transition_duration, function ()
                    {
                        $body.empty();
                        $body.append($new_content);
                        $(document).trigger("new_content_loaded");
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
        submitOnClick: function (e)
        {
            e.preventDefault();
            $(this).closest("form").submit();
            return false;
        },
        removeOnClick: function (e)
        {
            e.preventDefault();
            $(this).closest("[data-remove-target]").remove();
            return false;
        },
        expandOnClick: function (e)
        {
            e.preventDefault();
            var $this = $(this);
            $this.toggleClass("clicked");
            var $target = $("#" + $this.data('expand'));
            if ($target && $target.length > 0) {
                if (!$target.hasClass("active")) {
                    $target.fadeIn(400, function ()
                    {
                        $target.addClass("active");
                    });
                } else {
                    $target.fadeOut(300, function ()
                    {
                        $target.removeClass("active");
                    });
                }
            }
            return false;
        },
        removeNotifications: function ()
        {
            $(".notifications").each(function (i, el)
            {
                var $el = $(el);
                setTimeout(function ()
                {
                    $el.slideUp(300, function ()
                    {
                        $el.remove();
                    });
                }, 15000); //slide up after 15 seconds
            });
        },
        onFormSubmit: function (e)
        {
            e.preventDefault();
            var $form = $(this);
            var form_data = $form.serializeObject();
            if (form_data && typeof form_data.password === "string" && form_data.password.length > 0) {
                form_data.password = window.FES.singleton.encryption.encrypt(form_data.password);
            }
            $form.find(".help-block").remove();
            $form.find(".error").removeClass("error");
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
                    if (html.indexOf("http") === 0) {
                        window.location.href = html;
                        return;
                    }
                    window.FES.contentTransition('body', html, 600);
                },
                error: function (data)
                {
                    if (window.FES.isStringValidJson(data.responseText)) {
                        var json = JSON.parse(data.responseText);
                        for (var i in json) {
                            if (i === "fields") {
                                for (var j in json[i]) {
                                    $form.find('[name^="' + j + '"]').each(function (k, el)
                                    {
                                        var $inputEl = $(el);
                                        $inputEl.addClass("error");
                                        $inputEl.parent().append($('<div class="help-block error">' + json[i][j] + '</div>'));
                                    });
                                }
                            } else {
                                $form.prepend($('<div class="help-block error">' + json[i] + '</div>'))
                            }
                        }
                    } else {
                        console.error("Error Received: ", data.responseText);
                    }
                }
            });
            return false; //stop the click propagation
        },
        onDataLoadClick: function (e)
        {
            e.preventDefault();

            var $source = $(this),
                table = $source.data("load"),
                ajax_data = {
                    table: table,
                    getTemplate: "true"
                };

            if (typeof table !== "string" || table.length <= 0) { //table type is required
                return false;
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
                    window.FES.currentTable = {
                        id: ajax_data.table
                    };

                    window.FES.contentTransition("#datatable", response, 400);
                },
                error: function (response)
                {
                    console.error("error received: ", response.responseText);
                }
            });

            return false;
        },
        sendGaEvent: function(track_str){
            if(!track_str || track_str.length === 0){
                return false;
            }
            $(document).trigger("ga:init");
            var event_info = track_str.split('=');
            if(event_info.length > 1){
                var event = event_info[0];
                event_info.splice(0, 1);
                ga('send', 'event', event, event_info.join("="));
            } else {
                ga('send', event_info[0]);
            }
            return true;
        },
        startGaListeners: function(){
            $(document).on("ga:init", function(){
                if (window.FES.gaInitialized || !window.FES.googleAnalyticsId) {
                    return;
                }
                try {
                    ga('create', window.FES.googleAnalyticsId, 'auto');
                    window.FES.gaInitialized = true;
                } catch (e) {
                }
            });

            $(document).on("ga:send", function ()
            {
                window.FES.sendGaEvent('pageview');
            });

            $(document).on("ga:scan", function(){
                $(document).find('[data-auto-track]').each(function(i, el){
                    var $el = $(el),
                        track_str = $el.data("autoTrack");
                    $el.remove();
                    window.FES.sendGaEvent(track_str);
                });
            });

            $(document).on("content_transition_end", function(){
                $(document).trigger("ga:scan");
            });

            $(document).on('click', '[data-track]', function(e){
                window.FES.sendGaEvent($(this).data("track"));
            });
        },
        globalCellWriter: function (cellInfo, record)
        {
            var current_id = cellInfo.id;
            var cell_content = "<td class='data-cell " + current_id + "'>";
            var cell_data = record[current_id];
            if (cell_data) {
                if (current_id === "price" || current_id === "msrp" || current_id === "priceEach") {
                    cell_content += '$' + parseFloat(cell_data).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                } else
                    if (current_id === "creditLimit" || current_id === "amount") {
                        if (cell_data != 0) {
                            cell_content += '$' + parseFloat(cell_data).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                        } else {
                            cell_content += "N/a";
                        }
                    } else {
                        cell_content += cell_data;
                    }
            }
            return cell_content + "</td>";
        }
    };
})(jQuery, window);