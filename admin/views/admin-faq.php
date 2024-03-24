
<div id="faq-categories" class="metabox-holder">
    <div class="postbox">
        <h3 class="hndle"><span><?php _e('FAQ-Kategorien', 'psource-support'); ?></span></h3>
        <div class="inside">
            <div class="faq-categories-column">
                <h4><?php _e('Suche', 'psource-support'); ?></h4>
                <form method="post">
                    <input type="text" name="faq-s" value="<?= esc_attr( isset( $_POST['faq-s'] ) ? stripslashes_deep( $_POST['faq-s'] ) : '' ); ?>">
                    <?= wp_nonce_field('faq_search'); ?>
                    <?= submit_button(__('Suche', 'psource-support'), 'secondary', 'submit-faq-search'); ?>
                </form>
            </div>
            <?php
            $half_of_array = ceil(count($faq_categories) / 2);
            foreach (array_chunk($faq_categories, $half_of_array) as $chunk) :
            ?>
                <div class="faq-categories-column">
                    <ul>
                        <?php foreach ($chunk as $category) : ?>
                            <li><a href="#" class="button button-secondary faq-category" data-cat-id="<?= $category->cat_id; ?>"><?= $category->cat_name . ' (' . $category->faqs . ')'; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
            <div class="clear"></div>
        </div>
    </div>
</div>

<div id="faq-category-details" class="metabox-holder">
    <?php foreach ($faq_categories as $category) : ?>
        <div id="faq-category-<?= $category->cat_id; ?>" class="faq-category-wrap">
            <?php foreach ($category->answers as $faq) : ?>
                <div class="postbox closed" data-faq-id="<?= $faq->faq_id; ?>">
                    <div class="handlediv" title="<?php _e('Click to toggle'); ?>"><br></div>
                    <h3 class="hndle"><span><?= $faq->question; ?></span></h3>
                    <div class="inside">
                        <?php
                        $answer = preg_replace_callback('|^\s*(https?://[^\s"]+)\s*$|im', fn($matches) => embed_media($matches), $faq->answer);
                        $answer = apply_filters('the_content', $answer);
                        ?>

                        <div id="faq-answer-<?= $faq->faq_id; ?>">
                            <?= $answer; ?>
                            <p class="submit" data-faq-id="<?= $faq->faq_id; ?>">
                                <h4><u><?php _e('War dieses FAQ hilfreich?', 'psource-support'); ?></u></h4>
                                <button class="button-primary vote-button" data-faq-id="<?= $faq->faq_id; ?>" data-vote="yes"><?= __('Ja', 'psource-support'); ?></button>
                                <button href="#" class="button vote-button" data-faq-id="<?= $faq->faq_id; ?>" data-vote="no"><?= __('Nein', 'psource-support'); ?></button>
                                <span class="spinner support-system-spinner"></span>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
	jQuery(document).ready(function($) {
        $('.wrap').support_system();
    });
</script>
<style>
    .support-system-spinner {
        float: none;
    }
	.accordion ul li {
        list-style: disc;
        margin-left: 25px;
    }

    #faq-categories .faq-categories-column {
        width: 32%;
        min-width: 200px;
        float: left;
    }

    .faq-question-title {
        cursor: pointer;
        background: none;
        font-size: 15px;
        font-weight: normal;
        margin-left: 15px;
    }
</style>
