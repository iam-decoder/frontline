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
    });
})(jQuery, window);