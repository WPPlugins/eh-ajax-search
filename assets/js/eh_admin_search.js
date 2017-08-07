jQuery(document).ready(function (jQuery) {
    jQuery("#eh_search_history_wrap").on("click","#clear_history_a",function(){
        var ask = window.confirm("Are you sure you want to delete Search History?");
        if (ask) {
            var url = jQuery(this).prop("href");
            document.location.href = url;
        }
        else
        {
            return false;
        }
    });
});

