<?php
/**
 * AI Content Curator class for AI Newsletter Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Newsletter_Pro_AI_Curator {
    
    private $openai_api_key;
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('ai_newsletter_pro_settings', array());
        $this->openai_api_key = $this->settings['ai']['openai_api_key'] ?? '';
    }
    
    /**
     * Check if AI is configured
     */
    public function is_ai_enabled() {
        return !empty($this->openai_api_key);
    }
    
    /**
     * Auto-generate newsletter from recent posts
     */
    public function auto_generate_newsletter() {
        if (!$this->is_ai_enabled()) {
            return array('success' => false, 'message' => __('AI not configured', 'ai-newsletter-pro'));
        }
        
        // Get recent posts based on criteria
        $posts = $this->get_content_for_curation();
        
        if (empty($posts)) {
            return array('success' => false, 'message' => __('No suitable content found for curation', 'ai-newsletter-pro'));
        }
        
        // Generate newsletter content with AI
        $newsletter_content = $this->generate_newsletter_content($posts);
        
        if (!$newsletter_content) {
            return array('success' => false, 'message' => __('Failed to generate newsletter content', 'ai-newsletter-pro'));
        }
        
        // Create campaign
        $campaign_manager = new AI_Newsletter_Pro_Campaign_Manager();
        $result = $campaign_manager->create_campaign(
            $newsletter_content['title'],
            $newsletter_content['subject'],
            $newsletter_content['content'],
            array(
                'type' => 'auto_ai',
                'status' => 'draft'
            )
        );
        
        return $result;
    }
    
    /**
     * Get content for curation based on settings
     */
    private function get_content_for_curation() {
        $criteria = $this->settings['ai']['content_selection_criteria'] ?? 'engagement';
        $max_articles = $this->settings['ai']['max_articles'] ?? 5;
        
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $max_articles * 2, // Get more to filter from
            'date_query' => array(
                array(
                    'after' => '1 week ago'
                )
            )
        );
        
        switch ($criteria) {
            case 'recent':
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
                
            case 'engagement':
                $args['orderby'] = 'comment_count';
                $args['order'] = 'DESC';
                break;
                
            case 'quality':
                // Use AI to assess quality
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
                
            case 'mixed':
                $args['orderby'] = 'rand';
                break;
        }
        
        $posts = get_posts($args);
        
        // If using quality criteria, filter with AI
        if ($criteria === 'quality') {
            $posts = $this->filter_posts_by_quality($posts);
        }
        
        // Limit to max articles
        return array_slice($posts, 0, $max_articles);
    }
    
    /**
     * Filter posts by AI-assessed quality
     */
    private function filter_posts_by_quality($posts) {
        $quality_scores = array();
        
        foreach ($posts as $post) {
            $score = $this->assess_post_quality($post);
            if ($score > 0.6) { // Only include high-quality posts
                $quality_scores[$post->ID] = $score;
            }
        }
        
        // Sort by quality score
        arsort($quality_scores);
        
        // Return posts in quality order
        $filtered_posts = array();
        foreach (array_keys($quality_scores) as $post_id) {
            foreach ($posts as $post) {
                if ($post->ID == $post_id) {
                    $filtered_posts[] = $post;
                    break;
                }
            }
        }
        
        return $filtered_posts;
    }
    
    /**
     * Assess post quality using AI
     */
    private function assess_post_quality($post) {
        $prompt = "Rate the quality of this blog post on a scale of 0-1 based on readability, value, and engagement potential. Only respond with a number between 0 and 1.\n\nTitle: {$post->post_title}\n\nContent: " . substr(strip_tags($post->post_content), 0, 500);
        
        $response = $this->call_openai_api($prompt, 0.3, 20);
        
        if ($response && preg_match('/(\d+\.?\d*)/', $response, $matches)) {
            return (float) $matches[1];
        }
        
        return 0.5; // Default neutral score
    }
    
    /**
     * Generate newsletter content using AI
     */
    private function generate_newsletter_content($posts) {
        $tone = $this->settings['ai']['ai_tone'] ?? 'professional';
        $length = $this->settings['ai']['ai_length'] ?? 'medium';
        $site_name = get_bloginfo('name');
        
        // Prepare post summaries
        $post_summaries = array();
        foreach ($posts as $post) {
            $post_summaries[] = array(
                'title' => $post->post_title,
                'url' => get_permalink($post->ID),
                'excerpt' => $this->generate_post_summary($post, $length)
            );
        }
        
        // Generate newsletter structure
        $newsletter_title = $this->generate_newsletter_title($posts);
        $newsletter_subject = $this->generate_email_subject($newsletter_title, $tone);
        $newsletter_intro = $this->generate_newsletter_intro($tone);
        $newsletter_content = $this->compile_newsletter_content($newsletter_intro, $post_summaries, $tone);
        
        return array(
            'title' => $newsletter_title,
            'subject' => $newsletter_subject,
            'content' => $newsletter_content
        );
    }
    
    /**
     * Generate post summary using AI
     */
    private function generate_post_summary($post, $length) {
        $length_instructions = array(
            'brief' => 'in 1-2 sentences',
            'medium' => 'in 2-3 sentences', 
            'detailed' => 'in 3-4 sentences'
        );
        
        $instruction = $length_instructions[$length] ?? $length_instructions['medium'];
        
        $prompt = "Summarize this blog post {$instruction}. Make it engaging and highlight the key value for readers:\n\nTitle: {$post->post_title}\n\nContent: " . substr(strip_tags($post->post_content), 0, 1000);
        
        $summary = $this->call_openai_api($prompt, 0.7, 150);
        
        return $summary ?: substr(strip_tags($post->post_content), 0, 200) . '...';
    }
    
    /**
     * Generate newsletter title
     */
    private function generate_newsletter_title($posts) {
        $post_titles = array_map(function($post) {
            return $post->post_title;
        }, $posts);
        
        $prompt = "Create a catchy newsletter title that encompasses these blog post topics: " . implode(', ', $post_titles) . ". Keep it under 60 characters and make it engaging.";
        
        $title = $this->call_openai_api($prompt, 0.8, 50);
        
        return $title ?: sprintf(__('Latest Updates from %s', 'ai-newsletter-pro'), get_bloginfo('name'));
    }
    
    /**
     * Generate email subject line
     */
    private function generate_email_subject($newsletter_title, $tone) {
        $tone_instructions = array(
            'professional' => 'professional and informative',
            'friendly' => 'warm and friendly',
            'casual' => 'casual and conversational',
            'enthusiastic' => 'excited and energetic'
        );
        
        $tone_instruction = $tone_instructions[$tone] ?? $tone_instructions['professional'];
        
        $prompt = "Create an email subject line for a newsletter titled '{$newsletter_title}'. Make it {$tone_instruction}, under 50 characters, and designed to increase open rates.";
        
        $subject = $this->call_openai_api($prompt, 0.8, 30);
        
        return $subject ?: $newsletter_title;
    }
    
    /**
     * Generate newsletter introduction
     */
    private function generate_newsletter_intro($tone) {
        $tone_instructions = array(
            'professional' => 'professional and informative',
            'friendly' => 'warm and welcoming',
            'casual' => 'casual and conversational',
            'enthusiastic' => 'excited and energetic'
        );
        
        $tone_instruction = $tone_instructions[$tone] ?? $tone_instructions['professional'];
        
        $prompt = "Write a brief, {$tone_instruction} introduction for a newsletter from " . get_bloginfo('name') . ". Keep it 2-3 sentences and welcome readers to the latest updates.";
        
        $intro = $this->call_openai_api($prompt, 0.7, 100);
        
        return $intro ?: sprintf(__('Welcome to the latest updates from %s! Here are our top stories this week.', 'ai-newsletter-pro'), get_bloginfo('name'));
    }
    
    /**
     * Compile full newsletter content
     */
    private function compile_newsletter_content($intro, $post_summaries, $tone) {
        $content = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>";
        
        // Header
        $content .= "<div style='text-align: center; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px 10px 0 0;'>";
        $content .= "<h1 style='margin: 0; font-size: 24px;'>" . get_bloginfo('name') . "</h1>";
        $content .= "<p style='margin: 10px 0 0 0; opacity: 0.9;'>Newsletter</p>";
        $content .= "</div>";
        
        // Introduction
        $content .= "<div style='padding: 20px; background: #f8f9fa;'>";
        $content .= "<p style='margin: 0; font-size: 16px; line-height: 1.6;'>" . $intro . "</p>";
        $content .= "</div>";
        
        // Posts
        $content .= "<div style='padding: 20px;'>";
        foreach ($post_summaries as $index => $post) {
            $content .= "<div style='margin-bottom: 30px; padding-bottom: 20px;" . ($index < count($post_summaries) - 1 ? " border-bottom: 1px solid #eee;" : "") . "'>";
            $content .= "<h2 style='margin: 0 0 10px 0; font-size: 20px; color: #333;'><a href='" . esc_url($post['url']) . "' style='color: #667eea; text-decoration: none;'>" . esc_html($post['title']) . "</a></h2>";
            $content .= "<p style='margin: 0; font-size: 14px; line-height: 1.6; color: #666;'>" . esc_html($post['excerpt']) . "</p>";
            $content .= "<p style='margin: 10px 0 0 0;'><a href='" . esc_url($post['url']) . "' style='color: #667eea; text-decoration: none; font-weight: 500;'>Read more →</a></p>";
            $content .= "</div>";
        }
        $content .= "</div>";
        
        // Footer
        $content .= "<div style='padding: 20px; background: #f8f9fa; text-align: center; border-radius: 0 0 10px 10px;'>";
        $content .= "<p style='margin: 0; font-size: 14px; color: #666;'>Thank you for reading!</p>";
        $content .= "<p style='margin: 10px 0 0 0; font-size: 12px; color: #999;'>This newsletter was generated by AI and curated from our latest content.</p>";
        $content .= "</div>";
        
        $content .= "</div>";
        
        return $content;
    }
    
    /**
     * Call OpenAI API
     */
    private function call_openai_api($prompt, $temperature = 0.7, $max_tokens = 150) {
        if (empty($this->openai_api_key)) {
            return false;
        }
        
        $api_url = 'https://api.openai.com/v1/chat/completions';
        
        $data = array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => $temperature,
            'max_tokens' => $max_tokens
        );
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->openai_api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        );
        
        $response = wp_remote_post($api_url, $args);
        
        if (is_wp_error($response)) {
            error_log('AI Newsletter Pro: OpenAI API error - ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (isset($decoded['choices'][0]['message']['content'])) {
            return trim($decoded['choices'][0]['message']['content']);
        }
        
        error_log('AI Newsletter Pro: Unexpected OpenAI API response - ' . $body);
        return false;
    }
    
    /**
     * Generate subject line variations for A/B testing
     */
    public function generate_subject_variations($base_subject, $count = 3) {
        if (!$this->is_ai_enabled()) {
            return array($base_subject);
        }
        
        $prompt = "Generate {$count} different email subject line variations for: '{$base_subject}'. Each should be under 50 characters and optimized for open rates. Return only the subject lines, one per line.";
        
        $response = $this->call_openai_api($prompt, 0.8, 100);
        
        if ($response) {
            $variations = array_filter(array_map('trim', explode("\n", $response)));
            return array_slice($variations, 0, $count);
        }
        
        return array($base_subject);
    }
    
    /**
     * Improve existing content with AI
     */
    public function improve_content($content, $improvement_type = 'readability') {
        if (!$this->is_ai_enabled()) {
            return $content;
        }
        
        $prompts = array(
            'readability' => 'Improve the readability and flow of this content while maintaining its meaning:',
            'engagement' => 'Make this content more engaging and compelling while keeping the same information:',
            'tone' => 'Adjust the tone of this content to be more professional yet approachable:',
            'length' => 'Condense this content to be more concise while retaining key information:'
        );
        
        $prompt = ($prompts[$improvement_type] ?? $prompts['readability']) . "\n\n" . $content;
        
        $improved = $this->call_openai_api($prompt, 0.6, 500);
        
        return $improved ?: $content;
    }
}