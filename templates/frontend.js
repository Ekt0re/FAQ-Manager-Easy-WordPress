/**
 * Custom FAQ Manager - Frontend Scripts
 */
jQuery(document).ready(function($) {
    // Toggle FAQ items
    $('.faq-question').on('click', function() {
        var $this = $(this);
        var $answer = $this.next('.faq-answer');
        var $faqItem = $this.closest('.faq-item');
        
        // Toggle active state
        $this.toggleClass('active');
        
        // Toggle answer visibility with animation
        if ($this.hasClass('active')) {
            $answer.addClass('active');
            
            // Optional: Close other open FAQs (accordion behavior)
            // Uncomment these lines to enable accordion behavior
            /*
            $faqItem.siblings('.faq-item').find('.faq-question.active').removeClass('active');
            $faqItem.siblings('.faq-item').find('.faq-answer.active').removeClass('active');
            */
        } else {
            $answer.removeClass('active');
        }
    });
    
    // Check for URL hash to open specific FAQ
    function checkHash() {
        var hash = window.location.hash;
        if (hash) {
            var $targetFaq = $(hash);
            if ($targetFaq.length && $targetFaq.hasClass('faq-item')) {
                // Open the FAQ
                var $question = $targetFaq.find('.faq-question');
                var $answer = $targetFaq.find('.faq-answer');
                
                if (!$question.hasClass('active')) {
                    $question.addClass('active');
                    $answer.addClass('active');
                }
                
                // Scroll to the FAQ with slight delay for smooth animation
                setTimeout(function() {
                    $('html, body').animate({
                        scrollTop: $targetFaq.offset().top - 100
                    }, 500);
                }, 100);
            }
        }
    }
    
    // Run on page load
    checkHash();
    
    // Run when hash changes (user clicks a link to an FAQ)
    $(window).on('hashchange', checkHash);
}); 