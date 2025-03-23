# Coding Standards

This document describes the coding standards for the Ikigai Finder WordPress plugin.

## General Guidelines

- Follow WordPress Coding Standards
- Use meaningful variable and function names
- Write clear and concise comments
- Keep functions small and focused
- Use type hints where possible
- Document all public methods and classes

## File Structure

```
ikigai-finder/
├── admin/                 # Admin-specific files
├── includes/             # Core plugin files
├── js/                   # JavaScript files
├── css/                  # CSS files
├── languages/            # Translation files
├── tests/               # Test files
├── bin/                 # Shell scripts
├── docs/                # Documentation
├── ikigai-finder.php    # Main plugin file
├── composer.json        # Composer configuration
├── package.json         # NPM configuration
└── README.md           # Plugin documentation
```

## Naming Conventions

### Files
- Use kebab-case for file names: `class-ikigai-finder-block.php`
- Main plugin file: `ikigai-finder.php`
- Test files: `test-ikigai-finder-block.php`

### Classes
- Use PascalCase for class names: `Ikigai_Finder_Block`
- One class per file
- Class name should match file name

### Functions
- Use snake_case for function names: `ikigai_finder_get_option()`
- Prefix functions with plugin name: `ikigai_finder_`

### Variables
- Use camelCase for variable names: `$apiKey`
- Use descriptive names
- Prefix private class properties with underscore: `$_apiKey`

### Constants
- Use UPPER_SNAKE_CASE for constants: `IKIGAI_FINDER_VERSION`
- Prefix constants with plugin name: `IKIGAI_FINDER_`

## Code Style

### PHP
- Use spaces instead of tabs
- Indent with 4 spaces
- Use curly braces for all control structures
- Add spaces around operators
- Use type hints for parameters and return types
- Use strict type declarations

Example:
```php
declare(strict_types=1);

class Ikigai_Finder_Block {
    private string $_apiKey;

    public function __construct(string $apiKey) {
        $this->_apiKey = $apiKey;
    }

    public function get_api_key(): string {
        return $this->_apiKey;
    }
}
```

### JavaScript
- Use ES6+ features
- Use const/let instead of var
- Use arrow functions where appropriate
- Use template literals for strings
- Use destructuring and spread operators

Example:
```javascript
const { apiKey, model } = settings;
const handleSubmit = async (event) => {
    event.preventDefault();
    const response = await fetch('/api/chat', {
        method: 'POST',
        body: JSON.stringify({ message: input.value })
    });
};
```

### CSS
- Use BEM naming convention
- Use kebab-case for class names
- Use meaningful class names
- Keep selectors simple and specific
- Use CSS variables for colors and values

Example:
```css
.ikigai-finder-chat {
    --primary-color: #007bff;
    --secondary-color: #6c757d;
}

.ikigai-finder-chat__message {
    margin-bottom: 1rem;
}

.ikigai-finder-chat__message--user {
    background-color: var(--primary-color);
}
```

## Testing

- Write unit tests for all classes
- Use PHPUnit for PHP tests
- Use Jest for JavaScript tests
- Test edge cases and error conditions
- Mock external dependencies
- Use meaningful test names

Example:
```php
class Test_Ikigai_Finder_Block extends WP_UnitTestCase {
    public function test_should_validate_api_key(): void {
        $block = new Ikigai_Finder_Block('valid-key');
        $this->assertTrue($block->validate_api_key());
    }
}
```

## Documentation

- Document all public methods and classes
- Use PHPDoc blocks for PHP
- Use JSDoc blocks for JavaScript
- Keep documentation up to date
- Include examples where appropriate

Example:
```php
/**
 * Handles the chat request.
 *
 * @param string $message The user's message.
 * @return array The response data.
 * @throws Exception If the API request fails.
 */
public function handle_chat_request(string $message): array {
    // Implementation
}
```

## Version Control

- Use meaningful commit messages
- Follow semantic versioning
- Keep commits focused and atomic
- Use feature branches
- Review code before merging

## Security

- Validate and sanitize all input
- Escape all output
- Use nonces for forms
- Follow WordPress security best practices
- Keep dependencies up to date

## Performance

- Optimize database queries
- Cache where appropriate
- Minimize HTTP requests
- Optimize assets
- Use lazy loading where possible

## Accessibility

- Use semantic HTML
- Add ARIA attributes where needed
- Ensure keyboard navigation works
- Test with screen readers
- Follow WCAG guidelines

## Internationalization

- Use translation functions
- Make strings translatable
- Use translation files
- Test with different languages
- Consider RTL languages

Example:
```php
$message = esc_html__('Chat with AI', 'ikigai-finder');
$error = sprintf(
    /* translators: %s: error message */
    esc_html__('Error: %s', 'ikigai-finder'),
    $error_message
);
```
