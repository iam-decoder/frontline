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
                    console.warn("Error Received: ", data.responseText);
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
                    console.warn("error received: ", response.responseText);
                }
            });

            return false;
        },
        globalCellWriter: function(cellInfo, record)
        {
            var current_id = cellInfo.id;
            var cell_content = "<td class='data-cell " + current_id + "'>";
            var cell_data = record[current_id];
            if(cell_data){
                if(current_id === "price" || current_id === "msrp" || current_id === "priceEach") {
                    cell_content += '$' + parseFloat(cell_data).toFixed(2).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                } else if(current_id === "creditLimit" || current_id === "amount") {
                    if(cell_data != 0){
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