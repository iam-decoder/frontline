(function ($, window, undefined)
{
    $(document).ready(function ()
    { //before the fold

        //register listeners
        $(document).on('submit', 'form', window.FES.onFormSubmit);
        $(document).on("click", "[data-load]", window.FES.onDataLoadClick);
        $(document).on("click", "[data-submit]", window.FES.submitOnClick);
        $(document).on("click", "[data-remove]", window.FES.removeOnClick);
        $(document).on("click", "[data-expand]", window.FES.expandOnClick);
        $(document).on("remove_notifications", window.FES.removeNotifications);

        $(document).trigger("remove_notifications");

        $(document).on("content_transition_end", function ()
        {
            $(document).trigger("remove_notifications");
        });

        window.FES.startGaListeners();
    });

    $(function ()
    { //after the fold

        //fill FES with meta information
        window.FES.getMeta('#page-meta');

        $(document).trigger("ga:send");
        $(document).trigger("ga:scan");

        //listeners
        $(document).on("content_transition_end", function ()
        {

            if (!$.isEmptyObject(window.FES.currentTable)) {
                var $table = $('#' + window.FES.currentTable.id);
                if ($table.length > 0) {
                    $table.dynatable({
                        features: {
                            pushState: false,
                            search: true
                        },
                        inputs: {
                            queryEvent: 'blur change',
                            processingText: ""
                        },
                        writers: {
                            _cellWriter: window.FES.globalCellWriter
                        },
                        dataset: {
                            ajax: true,
                            ajaxUrl: window.FES.base_url + "/tabledata",
                            ajaxCache: null,
                            ajaxOnLoad: true,
                            ajaxMethod: 'GET',
                            ajaxDataType: 'json',
                            ajaxData: {
                                table: window.FES.currentTable.id
                            },
                            perPageDefault: 50,
                            perPageOptions: [20, 50, 100, 200],
                            records: []
                        }
                    });
                }
            }
        });
    });
})(jQuery, window);