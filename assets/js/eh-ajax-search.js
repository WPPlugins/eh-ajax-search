jQuery(document).ready(function (jQuery) {
    jQuery(".eh-ajax-search-div").on("focusin","#eh-ajax-search-text",function(){
        jQuery(".eh-ajax-search-div").css("width","100%");
        jQuery('#eh-ajax-search-text').removeClass('eh-ajax-search-icon');
    });
    jQuery(".eh-ajax-search-div").on("focusout","#eh-ajax-search-text",function(){
        jQuery(".eh-ajax-search-div").css("width","230px");
        jQuery('#eh-ajax-search-text').addClass('eh-ajax-search-icon');
    });
    var timeoutID = null;
    function EHstartSearch(str) {
        if(str !== "")
        {
            jQuery('#eh-ajax-search-text').addClass('eh-ajax-search-loader');
            jQuery.ajax({
                type: "POST",
                url: eh_ajax_search_object.ajax_url,
                data: {
                    action: 'eh_ajax_search_data',
                    search: eh_ajax_search_object.search,
                    search_in : eh_ajax_search_object.search_in,
                    show_thumbs: eh_ajax_search_object.show_thumbs,
                    show_contents: eh_ajax_search_object.show_contents,
                    q:str
                },
                success: function (data)
                {
                    var parse = JSON.parse(data);
                    if(parse.total_count !== 0)
                    {
                        var count = (parse.total_count <= 10)?parse.total_count:10;
                        var html = '<ul class="eh-ajax-search-result-ul">';
                        var items = parse.items;
                        for(i=0;i<count;i++)
                        {
                            var content = "";
                            if(eh_ajax_search_object.show_contents === "yes")
                            {
                                content = '<br><div class="eh-ajax-search-result-content">'+items[i]['content']+'</div>';
                            }
                            var thumb = "";
                            if(eh_ajax_search_object.show_thumbs === "yes" && items[i]['thumb'] !== false)
                            {
                                thumb = '<img src="'+items[i]['thumb']+'" width="32" height="32" class="eh-ajax-search-result-thumb">';
                            }
                            var target = "";
                            if(eh_ajax_search_object.target === "new")
                            {
                                target = 'target="_blank"';
                            }
                            html+='<li>'+thumb+'<a href="'+items[i]['guid']+'" '+target+'>'+items[i]['title']+' ( <span>'+items[i]['type']+'</span> )</a>'+content+'</li>';
                        }
                        html+='</ul>';
                        jQuery(".eh-ajax-search-append").html(html);
                        jQuery('#eh-ajax-search-text').removeClass('eh-ajax-search-loader');
                        jQuery(".eh-ajax-search-result").show('fast');
                    }
                    else
                    {
                        var html = '<ul class="eh-ajax-search-result-ul"><li>No Results Found</li></ul>';
                        jQuery(".eh-ajax-search-append").html(html);
                        jQuery('#eh-ajax-search-text').removeClass('eh-ajax-search-loader');
                        jQuery(".eh-ajax-search-result").show('fast');
                    }
                }
            });
        }
        else
        {
            jQuery(".eh-ajax-search-result").hide('fast');
            jQuery(".eh-ajax-search-append").html("");
        }
    }
    jQuery('.eh-ajax-search-div').on('keyup',"#eh-ajax-search-text",function(e) {
        clearTimeout(timeoutID);
        timeoutID = setTimeout(EHstartSearch.bind(undefined, e.target.value), 500);
    });
    jQuery('.eh-ajax-search-div').on('submit','form#eh-ajax-search-form',function (e) {
        var search = jQuery("#eh-ajax-search-text").val();
        if(eh_ajax_search_object.save_results === "yes")
        {
            jQuery.ajax({
                type: "POST",
                url: eh_ajax_search_object.ajax_url,
                data: {
                    action: 'eh_ajax_search_data_save',
                    search: search
                },
                success: function (data)
                {
                    window.location=eh_ajax_search_object.home_url+'?s='+search;
                }
            });
        }
        else
        {
            window.location=eh_ajax_search_object.home_url+'?s='+search;
        }
        e.preventDefault(); // avoid to execute the actual submit of the form.
    });
});