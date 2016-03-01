(function ($, window, undefined)
{
    $(document).ready(function ()
    { //before the fold

        //register listeners
        $(document).on('submit', 'form', window.FES.onFormSubmit);
        $(document).on("click", "[data-load]", window.FES.onDataLoadClick);

    });

    $(function ()
    { //after the fold

        //fill FES with meta information
        window.FES.getMeta('#page-meta');

        //listeners
//        $(document).on("new_content_loaded", function ()
        $(document).on("content_transition_end", function ()
        {

            if(!$.isEmptyObject(window.FES.currentTable)) {
                var $table = $('#' + window.FES.currentTable.id);
                if ($table.length > 0) {
                    $table.dynatable({
                        features: {
                            pushState: false,
                            search: false
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
                            perPageOptions: [10, 20, 50, 100],
                            records: []
                        }
                    });
                }
            }
        });
    });
})(jQuery, window);