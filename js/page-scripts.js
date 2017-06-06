/*
 *  These scripts are for the Resource Center Template page.
 */

//jQuery won't conflict with other libraries that could use $
jQuery.noConflict();

//Parses URL into substrings and returns array of parameters.
function GetURLParameter(Param)
{
    var pageURL = window.location.search.substring(1);
    var URLVariables = pageURL.split('&');
    for (var i=0; i< URLVariables.length; i++)
    {
        var ParameterName = URLVariables[i].split('=');
        if (ParameterName[0] == Param)
        {
            return ParameterName[1];
        }
    }
}

jQuery(function($){
    var topic = GetURLParameter('topic-filter');
    var type = GetURLParameter('type-filter');
    var search = GetURLParameter('k_search');
    if (topic != null)
    {
        if (topic == "Browse+by+Topic" || topic == "Browse%20by%20Topic")
        {
            //Do Nothing
        }
        else {
            $('#topic-menu').val(topic);
        }
    }
    if (type != null)
    {
        if (type == "Browse+by+Type" || type == "Browse%20by%20Type")
        {
            //Do Nothing
        }
        else
        {
            $('#type-menu').val(type);
        }
    }
    if (search != null)
    {
        $('.resource-input').val(search);
    }

    //Replaces select menus with jQuery UI version.
    $('#topic-menu').selectmenu();
    $('#type-menu').selectmenu();

    //Handles toggle of search bar
    $('.search_form_link').on('click', function(e){
        e.preventDefault();
        $('.resource_bar_search').toggle(500);
    });
    $('.search_form_close').on('click', function(e){
        e.preventDefault();
        $('.resource_bar_search').hide(500);
    });
});
