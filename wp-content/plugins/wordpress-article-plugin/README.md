# Article Navigator WordPress Plugin

A custom WordPress plugin that creates an interactive article navigation system with a sticky sidebar and category filtering.

## Features

- **Sticky Sidebar**: 30% width sidebar with all posts and featured images
- **Category Filtering**: Dynamic category buttons that filter posts
- **AJAX Loading**: Smooth content loading without page refresh
- **Responsive Design**: Mobile-friendly layout
- **Keyboard Navigation**: Arrow key navigation through posts
- **Default Behavior**: Shows first post when category is selected
- **Shortcode Support**: Easy integration anywhere with `[article_navigator]`

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use the shortcode `[article_navigator]` in any post, page, or widget

## Shortcode Usage

Basic usage:
\`\`\`
[article_navigator]
\`\`\`

With parameters:
\`\`\`
[article_navigator posts_per_page="15" exclude_categories="1,5" include_categories="2,3,4"]
\`\`\`

### Shortcode Parameters

- `posts_per_page` (default: 10) - Number of posts to display
- `exclude_categories` - Comma-separated list of category IDs to exclude
- `include_categories` - Comma-separated list of category IDs to include

## File Structure

\`\`\`
wp-article-navigator/
├── wp-article-navigator.php (Main plugin file)
├── assets/
│   ├── article-navigator.css (Styles)
│   ├── article-navigator.js (JavaScript functionality)
│   └── default-image.jpg (Fallback image)
└── README.md
\`\`\`

## Customization

The plugin is built with customization in mind. You can:

- Modify CSS in `assets/article-navigator.css`
- Extend JavaScript functionality in `assets/article-navigator.js`
- Override templates by copying them to your theme
- Add custom hooks and filters

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- IE11+ (limited support)

## Requirements

- WordPress 5.0+
- PHP 7.4+
- jQuery (included with WordPress)

## License

GPL v2 or later
