<?php
/**
 * Template per il blocco FAQ
 * 
 * Utilizzato sia per il blocco Gutenberg che per lo shortcode
 */

// Verifica che $faqs sia definito e non vuoto
if (!isset($faqs) || empty($faqs)) {
    return '<div class="custom-faq-empty">' . __('Nessuna FAQ trovata.', 'custom-faq-manager') . '</div>';
}

$unique_id = 'custom-faq-' . uniqid();
?>

<div class="custom-faq-container" id="<?php echo esc_attr($unique_id); ?>">
    <?php if (!empty($title)) : ?>
        <<?php echo esc_attr($title_tag); ?> class="custom-faq-title"><?php echo esc_html($title); ?></<?php echo esc_attr($title_tag); ?>>
    <?php endif; ?>
    
    <div class="custom-faq-list">
        <?php foreach ($faqs as $index => $faq) : 
            // Genera ID univoco per ogni domanda
            $faq_id = sanitize_title($faq->post_title) . '-' . $faq->ID;
        ?>
            <div class="custom-faq-item" data-faq-id="<?php echo esc_attr($faq_id); ?>">
                <div class="custom-faq-question">
                    <span class="custom-faq-icon"></span>
                    <<?php echo esc_attr($question_tag); ?> class="custom-faq-question-text">
                        <?php echo wp_kses_post($faq->post_title); ?>
                    </<?php echo esc_attr($question_tag); ?>>
                </div>
                <div class="custom-faq-answer">
                    <div class="custom-faq-answer-content">
                        <?php echo wp_kses_post(apply_filters('the_content', $faq->post_content)); ?>
                    </div>
                </div>
                
                <?php 
                // Stile personalizzato per la FAQ
                $custom_css = get_post_meta($faq->ID, '_faq_custom_css', true);
                if (!empty($custom_css)) : 
                ?>
                <style>
                    #<?php echo esc_attr($unique_id); ?> .custom-faq-item[data-faq-id="<?php echo esc_attr($faq_id); ?>"] {
                        <?php echo strip_tags($custom_css); ?>
                    }
                </style>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div> 