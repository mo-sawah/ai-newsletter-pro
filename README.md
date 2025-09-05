# AI Newsletter Pro - WordPress Plugin

A comprehensive newsletter plugin with AI-powered content curation, multiple widget layouts, and seamless email service integrations.

## Version 1.0.3

## Features

### Core Functionality

- ✅ **Subscriber Management** - Complete subscriber database with status tracking
- ✅ **Multiple Widget Types** - Popup modals, inline forms, floating widgets, banners
- ✅ **Campaign Management** - Create, send, and track email campaigns
- ✅ **Analytics & Reporting** - Detailed performance tracking and insights
- ✅ **Email Service Integrations** - Mailchimp, ConvertKit, Zoho, SendGrid, ActiveCampaign
- ✅ **AI Content Curation** - Automated newsletter generation using OpenAI
- ✅ **Shortcodes** - Easy form embedding with `[ai_newsletter_form]`
- ✅ **GDPR Compliance** - Privacy controls and consent management

### Widget Features

- **Popup Modals** - Time, scroll, and exit-intent triggers
- **Inline Forms** - Seamlessly embed in content
- **Floating Widgets** - Sticky corner widgets
- **Banner Notifications** - Top/bottom page banners
- **Customizable Design** - Multiple styles and color schemes
- **Mobile Responsive** - Works perfectly on all devices

### AI-Powered Features

- **Smart Content Curation** - AI selects best posts for newsletters
- **Automated Generation** - Create newsletters from recent content
- **Multiple Writing Tones** - Professional, friendly, casual, enthusiastic
- **Quality Assessment** - AI rates content quality
- **Subject Line Optimization** - Generate compelling email subjects

## Installation

1. Upload the plugin files to `/wp-content/plugins/ai-newsletter-pro/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Newsletter Pro' in your admin menu to configure

## File Structure

```
/wp-content/plugins/ai-newsletter-pro/
├── ai-newsletter-pro.php                 # Main plugin file
├── README.md                             # Plugin documentation
├── uninstall.php                         # Cleanup on uninstall
├── admin/                                # Admin dashboard
│   ├── class-admin.php                   # Admin main class
│   ├── admin-dashboard.php               # Dashboard page
│   ├── admin-widgets.php                 # Widget management
│   ├── admin-campaigns.php               # Campaign management
│   ├── admin-subscribers.php             # Subscriber management
│   ├── admin-integrations.php            # Email service integrations
│   ├── admin-settings.php                # Plugin settings
│   └── admin-analytics.php               # Analytics page
├── includes/                             # Core functionality
│   ├── class-database.php                # Database operations
│   ├── class-widget-manager.php          # Widget system
│   ├── class-email-services.php          # Email service integrations
│   ├── class-ai-curator.php              # AI content curation
│   ├── class-campaign-manager.php        # Campaign handling
│   ├── class-subscriber-manager.php      # Subscriber management
│   ├── class-analytics.php               # Analytics tracking
│   └── class-shortcodes.php              # Shortcode functionality
├── integrations/                         # Email service adapters
│   ├── class-mailchimp.php               # Mailchimp integration
│   ├── class-convertkit.php              # ConvertKit integration
│   └── ... (other service integrations)
├── public/                               # Frontend functionality
│   ├── js/                               # JavaScript files
│   │   ├── newsletter-widgets.js         # Widget functionality
│   │   └── newsletter-admin.js           # Admin dashboard JS
│   └── css/                              # Stylesheets
│       ├── newsletter-widgets.css        # Widget styles
│       └── newsletter-admin.css          # Admin styles
├── templates/                            # Template files
│   ├── widgets/                          # Widget templates
│   │   ├── popup-modal.php               # Popup widget template
│   │   ├── inline-widget.php             # Inline widget template
│   │   ├── floating-widget.php           # Floating widget template
│   │   └── banner-widget.php             # Banner widget template
│   └── emails/                           # Email templates
│       ├── welcome-email.php             # Welcome email template
│       ├── newsletter-template.php       # Newsletter template
│       └── unsubscribe-email.php         # Unsubscribe confirmation
└── languages/                            # Translation files
    ├── ai-newsletter-pro.pot             # Template file
    └── ai-newsletter-pro-en_US.po        # English translations
```

## Quick Start Guide

### 1. Basic Setup

1. **Activate Plugin** - Go to Plugins → Activate AI Newsletter Pro
2. **Configure Settings** - Visit Newsletter Pro → Settings
3. **Set Email Details** - Add your from name and email address
4. **Choose Widget Types** - Enable popup, floating, banner, or inline widgets

### 2. Create Your First Widget

1. Go to **Newsletter Pro → Widgets**
2. Click **Create Popup** (or your preferred type)
3. Customize title, subtitle, and button text
4. Set trigger conditions (time delay, scroll percentage, exit intent)
5. Choose design style and colors
6. Save and activate

### 3. Email Service Integration

1. Go to **Newsletter Pro → Integrations**
2. Choose your email service (Mailchimp, ConvertKit, etc.)
3. Enter API credentials
4. Test connection
5. Enable automatic syncing

### 4. AI Configuration (Optional)

1. Get OpenAI API key from https://platform.openai.com/api-keys
2. Go to **Newsletter Pro → Settings → AI Configuration**
3. Enter your API key
4. Configure content selection criteria
5. Set newsletter frequency for auto-generation

## Shortcodes

### Newsletter Form

```php
[ai_newsletter_form style="inline" title="Subscribe" button="Join Now"]
```

**Parameters:**

- `style` - inline, popup, floating, banner
- `title` - Form headline
- `subtitle` - Description text
- `button` - Button text
- `placeholder` - Email input placeholder
- `class` - Additional CSS classes
- `show_privacy` - true/false
- `redirect` - URL to redirect after signup
- `source` - Source identifier for tracking

### Subscriber Count

```php
[ai_newsletter_count format="number" prefix="Join" suffix="subscribers"]
```

**Parameters:**

- `format` - number, short (1.2K), words
- `prefix` - Text before count
- `suffix` - Text after count
- `status` - subscribed, pending, all
- `class` - CSS classes

### Widget Display

```php
[ai_newsletter_widget id="123"]
[ai_newsletter_widget type="popup" title="Subscribe Now"]
```

## Email Service Setup

### Mailchimp

1. Log into Mailchimp account
2. Go to Account → Extras → API Keys
3. Create new API key
4. Go to Audience → Settings → Audience name and defaults
5. Copy Audience ID
6. Add both to Newsletter Pro → Integrations

### ConvertKit

1. Log into ConvertKit account
2. Go to Settings → Advanced → API
3. Copy API Key
4. Go to Forms and copy Form ID
5. Add both to Newsletter Pro → Integrations

### SendGrid

1. Log into SendGrid account
2. Go to Settings → API Keys
3. Create new API key with Full Access
4. Copy API key (won't be visible again)
5. Add to Newsletter Pro → Integrations

## AI Features Setup

### OpenAI Integration

1. **Get API Key** - Sign up at https://platform.openai.com
2. **Add Credits** - Purchase API credits for usage
3. **Configure Plugin** - Add API key in Settings
4. **Set Preferences** - Choose tone, length, criteria
5. **Auto-Generation** - Enable scheduled newsletter creation

### Content Curation Options

- **High Engagement** - Posts with most comments/views
- **Recent Posts** - Latest published content
- **AI Quality Assessment** - AI evaluates post quality
- **Mixed Strategy** - Combination approach

## Customization

### Template Override

Copy template files to your theme:

```
/wp-content/themes/your-theme/ai-newsletter-pro/widgets/popup-modal.php
```

### Custom Styling

Add CSS to your theme or use the built-in customizer:

```css
.ai-newsletter-popup {
  /* Your custom styles */
}
```

### Hooks and Filters

```php
// Modify form HTML before display
add_filter('ai_newsletter_form_html', 'your_function');

// Custom validation
add_filter('ai_newsletter_validate_email', 'your_validation');

// After successful subscription
add_action('ai_newsletter_subscriber_added', 'your_function');
```

## Performance

### Optimization Tips

- **Caching** - Uses WordPress transients for performance
- **Lazy Loading** - Widgets load only when needed
- **Minimal JS/CSS** - Optimized assets under 50KB total
- **Database Indexes** - Proper indexing on all tables
- **Rate Limiting** - Built-in API rate limiting

### Database Tables

- `wp_ai_newsletter_subscribers` - Subscriber data
- `wp_ai_newsletter_campaigns` - Email campaigns
- `wp_ai_newsletter_widgets` - Widget configurations
- `wp_ai_newsletter_analytics` - Performance tracking
- `wp_ai_newsletter_campaign_recipients` - Send tracking

## Troubleshooting

### Common Issues

**Widgets Not Appearing**

- Check widget is active in Newsletter Pro → Widgets
- Verify CSS/JS files are loaded
- Check for JavaScript errors in browser console

**Email Service Connection Failed**

- Verify API credentials are correct
- Check service status pages
- Test with different API endpoints

**Subscribers Not Syncing**

- Confirm email service integration is enabled
- Check sync logs in Integrations page
- Verify API rate limits aren't exceeded

**AI Features Not Working**

- Ensure OpenAI API key is valid
- Check API credit balance
- Verify internet connectivity

### Debug Mode

Enable WordPress debug mode in wp-config.php:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs in `/wp-content/debug.log` for AI Newsletter Pro errors.

## Security

- **Data Encryption** - Sensitive data encrypted in database
- **Nonce Verification** - All forms use WordPress nonces
- **Capability Checks** - Proper user permission verification
- **Sanitization** - All inputs sanitized and validated
- **GDPR Compliance** - Built-in privacy controls

## Support

- **Documentation** - Complete guides at plugin settings
- **Community** - WordPress.org support forums
- **Email Support** - Contact through plugin admin panel

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- SSL certificate (recommended)
- OpenAI API key (for AI features)

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Changelog

### Version 1.0.3

- Initial release
- Complete subscriber management system
- Multi-widget support with advanced triggers
- Email service integrations (5+ services)
- AI-powered content curation
- Comprehensive analytics dashboard
- GDPR compliance features
- Mobile-responsive design
- Shortcode support

---

**AI Newsletter Pro** - Transform your WordPress site into a powerful newsletter platform with intelligent automation and beautiful, converting signup forms.
