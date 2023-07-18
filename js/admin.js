jQuery(document).ready(function($) {
	/**
     * toggle deleted row class and add the code to the hidden input.
     */
    $('.delete-check').on('change', (evt) => {
        $this = $(evt.target);
        $this.closest('tr').toggleClass('deleted-row');
        $($this.siblings()[0]).val($this.prop('checked') ? $this.data("code_id_to_delete")  : "" );
	})

    /**
     * toggle the filters.
     */
    $('.table-wrapper .filters input').on('click', (evt) => {
        $('#code-search').val('');
        const $this = $(evt.target);
        const $all = $('.table-wrapper table tr');
        const $used = $('.table-wrapper table tr td.delete-td:not(:has(*)').closest('tr');
        const $unused = $('.table-wrapper table tr td.delete-td:has(*)').closest('tr');
        switch($this.attr('id')) {
            case 'all':
                $all.fadeIn();
            break;
            case  'unused':
                $used.fadeOut();
                $unused.fadeIn();
            break;
            case 'used':
                $unused.fadeOut();
                $used.fadeIn();
            break;
        }
    });

    /**
     * Search the codes and users
     */
    $('#code-search').on('keyup', evt => {
        $input = $(evt.target);
        const searchText = $input.val().toLowerCase();
        $('.table-wrapper table tbody tr:visible').filter((index, element) => {
            $(element).toggle($(element).text().toLowerCase().includes(searchText));
        });
    });
});