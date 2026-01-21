<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Translator.php';
require_once __DIR__ . '/../src/Database.php';

// Handle language selection
$lang = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'en';
if (isset($_GET['lang'])) {
    setcookie('lang', $lang, time() + (365 * 24 * 60 * 60), '/');
}

// Development mode - enable Tolgee in-context editing
// Enabled by: API key present, ?tolgeeDevelopment query param, or TOLGEE_API_KEY env var
$apiKey = getenv('TOLGEE_API_KEY') ?: '';
$isDevelopment = !empty($apiKey)
    || isset($_GET['tolgeeDevelopment']);

$translator = new Translator($lang, $isDevelopment);
$db = new Database();

// Simple translation function
// NOTE: Do NOT use htmlspecialchars here - it would corrupt the invisible characters!
// The invisible wrapper characters are safe Unicode and don't need escaping.
function t(string $key, array $params = []): string {
    global $translator;
    return $translator->t($key, $params);
}

$items = $db->getItems();
$languages = [
    'en' => 'English',
    'cs' => 'Česky',
    'fr' => 'Français',
    'de' => 'Deutsch',
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('app-title') ?></title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <div class="example">
        <nav class="navbar">
            <div></div>
            <select class="lang-selector" onchange="window.location.href='?lang=' + this.value">
                <?php foreach ($languages as $code => $name): ?>
                    <option value="<?= $code ?>" <?= $code === $lang ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </nav>

        <header>
            <img src="/img/appLogo.svg" alt="App Logo">
            <h1><?= t('app-title') ?></h1>
        </header>

        <div class="items">
            <form class="items__new-item" id="add-form">
                <input
                    type="text"
                    id="new-item-input"
                    placeholder="<?= t('add-item-input-placeholder') ?>"
                    autocomplete="off"
                >
                <button type="submit" class="button">
                    <img src="/img/iconAdd.svg" alt="">
                    <?= t('add-item-add-button') ?>
                </button>
            </form>

            <div class="items__list" id="items-list">
                <?php foreach ($items as $item): ?>
                    <div class="item" data-id="<?= $item['id'] ?>">
                        <div class="item__text"><?= htmlspecialchars($item['text']) ?></div>
                        <button onclick="deleteItem(<?= $item['id'] ?>)">
                            <?= t('delete-item-button') ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="items__buttons">
                <button class="button">
                    <img src="/img/iconShare.svg" alt="">
                    <?= t('share-button') ?>
                </button>
                <button class="button">
                    <img src="/img/iconMail.svg" alt="">
                    <?= t('send-via-email') ?>
                </button>
            </div>
        </div>
    </div>

    <script>
        const deleteButtonText = <?= json_encode(t('delete-item-button')) ?>;

        document.getElementById('add-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('new-item-input');
            const text = input.value.trim();

            if (!text) return;

            try {
                const response = await fetch('/api.php?action=add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ text })
                });

                const data = await response.json();

                if (data.item) {
                    const list = document.getElementById('items-list');
                    const div = document.createElement('div');
                    div.className = 'item';
                    div.dataset.id = data.item.id;
                    div.innerHTML = `
                        <div class="item__text">${escapeHtml(data.item.text)}</div>
                        <button onclick="deleteItem(${data.item.id})">${deleteButtonText}</button>
                    `;
                    list.appendChild(div);
                    input.value = '';
                }
            } catch (error) {
                console.error('Error adding item:', error);
            }
        });

        async function deleteItem(id) {
            try {
                const response = await fetch('/api.php?action=delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });

                const data = await response.json();

                if (data.success) {
                    const item = document.querySelector(`.item[data-id="${id}"]`);
                    if (item) {
                        item.remove();
                    }
                }
            } catch (error) {
                console.error('Error deleting item:', error);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>

    <?php if ($isDevelopment): ?>
    <!-- Tolgee In-Context Editing -->
    <script src="https://cdn.jsdelivr.net/npm/@tolgee/web/dist/tolgee-web.development.umd.min.js"></script>
    <script>
        const { Tolgee, DevTools, ObserverPlugin } = window['@tolgee/web'];
        const tolgee = Tolgee()
            .use(DevTools())
            .use(ObserverPlugin())
            .init({
                language: '<?= htmlspecialchars($lang) ?>',
                apiUrl: '<?= htmlspecialchars(getenv('TOLGEE_API_URL') ?: 'https://app.tolgee.io') ?>',
                apiKey: '<?= htmlspecialchars($apiKey) ?>',
                observerOptions: {
                    fullKeyEncode: true
                }
            });

        tolgee.run()
    </script>
    <?php endif; ?>
</body>
</html>
