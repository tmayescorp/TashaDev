<?php
global $post, $wilcityArgs;
?>
<multiple-products
        :post-id="<?php echo abs($post->ID); ?>"
        :settings='<?php echo json_encode($wilcityArgs); ?>'
        total-label="<?php echo esc_html__('Total', 'wilcity'); ?>"
        view-cart-label="<?php echo esc_html__('View Cart', 'wilcity'); ?>"
        checkout-label="<?php echo esc_html__('Checkout', 'wilcity'); ?>"
>
</multiple-products>
