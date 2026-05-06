;(function ($) {
	'use strict';

	$(document).ready(function () {
		let $select2 = $('.wiloke-select2');

		$select2.each(function () {
			let $this = $(this);
      const args = {
        action: $this.attr('ajax_action'),
        post_types: $this.attr('post_types'),
        post_status:$this.attr('post_status'),
        post_id: $('#post_ID').val()
      };

			if ($this.attr('id') === 'wilcity_my_products') {
        args = {
          ...args,
          mode: $('#wilcity_my_product_mode').val()
        }
      }

			$this.select2({
				ajax: {
					url: ajaxurl,
					data: function (params) {
						return {
						  ...args,
              q: params.term
            };
					},
					processResults: function (data, params) {
						if ( !data.success ){
							return false;
						}else{
							return typeof data.data.msg !== 'undefined' ? data.data.msg : data.data;
						}
					},
					cache: true
				},
				allowClear: true,
				placeholder: '',
				minimumInputLength: 1
			});
		});

	});

})(jQuery);
