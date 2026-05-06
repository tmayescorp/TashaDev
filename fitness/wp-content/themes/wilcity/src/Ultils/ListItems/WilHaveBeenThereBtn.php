<?php

namespace Wilcity\Ultils\ListItems;

use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\HaveBeenThereModel;

class WilHaveBeenThereBtn extends RenderableAbstract implements RenderableInterface
{
    public function render(): string
    {
        global $post;
        $postID = $this->getAttribute('postId', $post->ID);
        $classes = $this->getAttribute('wrapperClasses', 'list_link__2rDA1 text-ellipsis color-primary--hover');
        $isCheckedHaveBeenThere = \WilokeListingTools\Models\HaveBeenThereModel::isChecked($postID);
        $count = HaveBeenThereModel::count($postID);

        ob_start();
        ?>
        <wil-have-been-there-btn :post-id="<?php echo abs($postID); ?>"
                                 is-me-checked="<?php echo $isCheckedHaveBeenThere ? 'yes' : 'no' ?>"
                                 btn-name="<?php echo esc_attr($this->getAttribute('btnName')); ?>"
                                 default-count="<?php echo esc_attr($count); ?>"
                                 wrapper-classes="<?php echo esc_attr($classes); ?>"></wil-have-been-there-btn>
        <?php
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}
