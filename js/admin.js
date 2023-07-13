jQuery(document).ready(function($) {
	/**
     * toggle deleted row class and add the code to the hidden input.
     */
    $('.delete-check').on('change', function(evt) {
        $(this).closest('tr').toggleClass('deleted-row');
        $($(this).siblings()[0]).val($(this).prop('checked') ? $(this).data("code_id_to_delete")  : "" );
	})

});