<tfoot>
<tr>
	<th colspan="11">
		<div class="ui pagination menu" style="padding-top: 0 !important;">
			<?php for ($i = 1; $i <= $this->pagination; $i++ ) :
				$activated = $this->paged == $i ? 'active' : '';
				$this->aFilters['paged'] = $i;

				?>
				<a class="<?php echo esc_attr($activated); ?> item" href="<?php echo esc_url(add_query_arg
                ($this->aFilters, admin_url('admin.php')));
				?>"><?php echo
                    esc_html($i); ?></a>
			<?php endfor; ?>
		</div>
	</th>
</tr>
</tfoot>
