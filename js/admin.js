jQuery(document).ready(function($) {
    const allCodes = $('.group-code-td').map(function() {
        return $(this).data("code_id");
    }).get();
    localStorage.setItem('pmpro_group_discount_codes', allCodes);
    $('textarea#group_codes').on('input', function(evt) {
        const newCodes = $(this).val().split('\n');
        const oldCodes = localStorage.getItem('pmpro_group_discount_codes').split(',');
        const codesToAdd = newCodes.filter(x => !oldCodes.includes(x));
        const allCodes = [...new Set([...oldCodes, ...codesToAdd])];
        $(this).val(allCodes.join('\n'));
    });

	/**
     * Colect codes to delete on checkbox change
     */
    $('.delete-check').on('change', function() {
		const checked = $('.delete-check:checked').map(function() {
            return $(this).data("code_id_to_delete");
        }).get();
		$('#delete_codes_set').val(checked);
	});


});