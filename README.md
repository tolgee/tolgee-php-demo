# PHP Localization with In-Context Editing | Tolgee Integration Example

<p align="center">
  <b>Add visual in-context translation editing to PHP, Rails, Django, and other server-rendered applications</b>
</p>

<p align="center">
  <a href="https://github.com/tolgee/tolgee-platform/stargazers">
    <img src="https://img.shields.io/github/stars/tolgee/tolgee-platform?style=social" alt="Tolgee Localization Platform GitHub Stars">
  </a>
  <a href="https://github.com/tolgee/tolgee-js/stargazers">
    <img src="https://img.shields.io/github/stars/tolgee/tolgee-js?style=social&label=Tolgee%20JS" alt="Tolgee JavaScript SDK Stars">
  </a>
  <a href="https://tolgee.io/slack">
    <img src="https://img.shields.io/badge/Slack-Tolgee Community-blue?logo=slack" alt="Tolgee Slack Community">
  </a>
  <a href="https://docs.tolgee.io">
    <img src="https://img.shields.io/badge/Docs-docs.tolgee.io-blue" alt="Tolgee Documentation">
  </a>
</p>

---

## PHP Translation Management with Visual Editing

This repository demonstrates how to integrate **[Tolgee](https://tolgee.io)** in-context translation editing into **server-side rendered PHP applications**. Enable translators and developers to edit translations directly in the browser - no code changes required.

### Why In-Context Editing for PHP?

Traditional PHP localization workflows require developers to:
1. Find the translation key in code
2. Look up the key in JSON/PO files
3. Make changes
4. Refresh to see results

With Tolgee's in-context editing, translators simply **click on any text** to edit it instantly.

## Key Features

| Feature | Description |
|---------|-------------|
| ✏️ **In-Context Editor** | Click any text to translate - Alt+Click to edit |
| 🔄 **Real-time Sync** | Tolgee CLI watches and pulls translation changes automatically |
| 🐳 **Docker Ready** | One command setup with Docker Compose |
| 🌍 **Multi-language** | Built-in support for English, Czech, French, German |

## How PHP In-Context Translation Works

This integration uses **invisible Unicode markers** to connect rendered text with translation keys:

```
Rendered HTML:  "What To Pack" + invisible characters
                      ↓
Tolgee JS decodes:  {"key": "app-title", "namespace": ""}
                      ↓
Result:  Clickable, editable translation
```

### The Translation Flow

1. **PHP renders translations** with invisible key markers appended
2. **Tolgee Observer** scans the DOM and detects marked strings
3. **User holds Alt/Option** and clicks to open the translation editor
4. **Changes sync** via Tolgee API to your translation management platform
5. **CLI pulls updates** to your local JSON translation files

## Quick Start Guide

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) and Docker Compose
- [Tolgee account](https://app.tolgee.io) (free tier available)
- Project API key from Tolgee

### Installation

```bash
# Clone the PHP localization example
git clone https://github.com/tolgee/tolgee-php-demo.git
cd tolgee-php-demo

# Configure your Tolgee credentials
cat > .env << EOF
TOLGEE_API_URL=https://app.tolgee.io
TOLGEE_API_KEY=your-project-api-key
TOLGEE_DEV_MODE=true
EOF

# Start the application with translation sync
docker compose --profile dev up
```

### Try In-Context Editing

1. Open [http://localhost:8080](http://localhost:8080)
2. Hold **Alt** (Windows/Linux) or **Option** (Mac)
3. Hover over any translated text - it will highlight
4. Click to open the translation editor
5. Save changes - they sync automatically

## Project Structure

```
tolgee-php-demo/
├── docker-compose.yml        # PHP 8.4 + Tolgee CLI services
├── Dockerfile                # Apache + PHP + SQLite
├── .tolgeerc.json            # Tolgee CLI configuration
│
├── public/                   # Web root
│   ├── index.php             # Main application with i18n
│   ├── api.php               # REST API endpoints
│   ├── style.css             # Application styles
│   └── i18n/                 # Translation files
│       ├── en.json           # English translations
│       ├── cs.json           # Czech translations
│       ├── fr.json           # French translations
│       └── de.json           # German translations
│
└── src/                      # PHP source
    ├── Translator.php        # Translation function with wrapping
    ├── InvisibleWrapper.php  # Invisible character encoding
    └── Database.php          # SQLite database handler
```

## Understanding the Invisible Wrapper

The `InvisibleWrapper` class encodes translation keys using zero-width Unicode characters:

```php
class InvisibleWrapper
{
    // Zero-Width Non-Joiner (0) and Zero-Width Joiner (1)
    const INVISIBLE_CHARACTERS = ["\u{200C}", "\u{200D}"];

    public function wrap($key, $namespace, $translation)
    {
        // Encode {"k":"key","n":"namespace"} as invisible binary
        $data = json_encode(['k' => $key, 'n' => $namespace ?: '']);
        return $translation . $this->encodeToInvisible($data);
    }
}
```

These characters are:
- ✅ Invisible to users
- ✅ Safe in HTML
- ✅ Preserved in copy/paste
- ✅ Detected by Tolgee Observer

## Enabling Development Mode

By default, you don't want to expose Tolgee dev tools or in-context editing capabilities to end users in production. The API key should never be bundled into client-side code, and the invisible character wrapping adds unnecessary overhead.

However, **team members like translators or product managers often need to edit strings directly on production** - seeing translations in their real context is invaluable for quality.

### The Tolgee Browser Plugin Solution

The [Tolgee Browser Plugin](https://docs.tolgee.io/browser-plugin) solves this elegantly. It can **inject the API key and dev tools directly into Tolgee JS** on the client side, enabling in-context editing without any server-side credentials:

1. Install the [Tolgee Chrome Extension](https://chromewebstore.google.com/detail/tolgee-tools/hacnbapajkkfohnonhbmegojnddagfnj) or [Firefox Add-on](https://addons.mozilla.org/en-US/firefox/addon/tolgee-tools/)
2. Configure the plugin with your Tolgee API key
3. Visit your app with `?tolgeeDevelopment` parameter to load Tolgee JS
4. The plugin injects credentials - in-context editing works without exposing keys server-side

This way, only team members with the plugin installed can edit translations, while regular users see a normal production site.

### Method Used in This Example

```php
// Development mode is enabled when:
// - API key is provided (local development with .env)
// - ?tolgeeDevelopment query param is present (for browser plugin)
$isDevelopment = !empty($apiKey) || isset($_GET['tolgeeDevelopment']);
```

### Alternative Approaches for Your Application

Depending on your security requirements, consider these patterns:

#### Session-Based Toggle
```php
// Enable via: /toggle-tolgee?enable=1
if (isset($_GET['enable'])) {
    $_SESSION['tolgee_dev'] = $_GET['enable'] === '1';
}
$isDevelopment = $_SESSION['tolgee_dev'] ?? false;
```

#### Staging Environment Detection
```php
// Auto-enable on staging subdomains
$host = $_SERVER['HTTP_HOST'] ?? '';
$isDevelopment = str_contains($host, 'staging.')
    || str_contains($host, 'dev.')
    || $host === 'localhost';
```

#### IP Allowlist
```php
// Only enable for office/VPN IPs
$allowedIPs = ['192.168.1.0/24', '10.0.0.0/8'];
$isDevelopment = ipInRange($_SERVER['REMOTE_ADDR'], $allowedIPs);
```

#### Authenticated Users Only
```php
// Only for logged-in translators
$isDevelopment = isLoggedIn() && currentUser()->hasRole('translator');
```

## Production Deployment

In production, development mode should be **disabled** to:
- Remove invisible character wrapping (cleaner HTML)
- Skip loading Tolgee JS (faster page loads)

Simply ensure no development triggers are active:
- No `TOLGEE_API_KEY` in production env
- Don't expose `?tolgeeDevelopment` parameter to end users

## Documentation & Resources

- 📖 [In-Context Editing for Backend Apps](https://docs.tolgee.io/js-sdk/integrations/backend-rendered/overview) - Complete integration guide
- 📖 [Setup Guide](https://docs.tolgee.io/js-sdk/integrations/backend-rendered/setup) - Step-by-step instructions
- 📖 [Tolgee CLI Documentation](https://docs.tolgee.io/tolgee-cli) - CLI commands and configuration
- 🎓 [Tolgee Platform Docs](https://docs.tolgee.io) - Full documentation

## Related Tolgee Integrations

- [React + Tolgee](https://github.com/tolgee/react-example) - React SPA integration
- [Next.js + Tolgee](https://github.com/tolgee/next-example) - Next.js with SSR
- [Vue + Tolgee](https://github.com/tolgee/vue-example) - Vue.js integration
- [Svelte + Tolgee](https://github.com/tolgee/svelte-example) - Svelte integration

## Contributing

Contributions are welcome! Please read our [Contributing Guide](https://github.com/tolgee/tolgee-platform/blob/main/CONTRIBUTING.md) before submitting PRs.

---

<p align="center">
  <a href="https://tolgee.io">
    <img src="./logo.svg" alt="Tolgee Localization Platform Logo" width="200">
  </a>
</p>

<p align="center">
  <b>Tolgee - Localization platform developers enjoy</b><br>
  <a href="https://tolgee.io">Website</a> •
  <a href="https://docs.tolgee.io">Documentation</a> •
  <a href="https://app.tolgee.io">Get Started Free</a>
</p>
