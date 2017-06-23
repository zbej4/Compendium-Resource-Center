/*
 *  These scripts are for the Resource Center Admin page.
 */

//jQuery won't conflict with other libraries that could use $
jQuery.noConflict();

jQuery(function($){
    $('.custom-type-delete').on('click', function(e){
         var $d = $(this).data('custom-delete');
         //Find the correct row
         var $row = $('.custom-types tr[data-custom-type=' + $d + ']');
         $row.remove();
    });
});

