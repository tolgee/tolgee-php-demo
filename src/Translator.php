<?php

declare(strict_types=1);

require_once __DIR__ . '/InvisibleWrapper.php';

class Translator
{
    private array $translations = [];
    private string $currentLanguage;
    private string $fallbackLanguage = 'en';
    private array $availableLanguages = ['en', 'cs', 'fr', 'de'];
    private bool $isDevelopment;
    private ?InvisibleWrapper $wrapper = null;

    public function __construct(string $language = 'en', bool $isDevelopment = false)
    {
        $this->isDevelopment = $isDevelopment;
        if ($isDevelopment) {
            $this->wrapper = new InvisibleWrapper();
        }
        $this->setLanguage($language);
    }

    public function setLanguage(string $language): void
    {
        if (!in_array($language, $this->availableLanguages)) {
            $language = $this->fallbackLanguage;
        }
        $this->currentLanguage = $language;
        $this->loadTranslations();
    }

    private function loadTranslations(): void
    {
        // Use document root for Docker compatibility
        $basePath = $_SERVER['DOCUMENT_ROOT'] ?? __DIR__ . '/../public';
        $filePath = $basePath . '/i18n/' . $this->currentLanguage . '.json';

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $this->translations = json_decode($content, true) ?? [];
        }

        // Load fallback language if not English
        if ($this->currentLanguage !== $this->fallbackLanguage) {
            $fallbackPath = $basePath . '/i18n/' . $this->fallbackLanguage . '.json';
            if (file_exists($fallbackPath)) {
                $fallbackContent = file_get_contents($fallbackPath);
                $fallbackTranslations = json_decode($fallbackContent, true) ?? [];
                // Merge fallback with current, current takes precedence
                $this->translations = array_merge($fallbackTranslations, $this->translations);
            }
        }
    }

    public function t(string $key, array $params = [], ?string $namespace = null): string
    {
        $translation = $this->translations[$key] ?? $key;

        // Use ICU MessageFormat if we have parameters
        if (!empty($params)) {
            $formatted = $this->formatWithICU($translation, $params);
            if ($formatted !== null) {
                $translation = $formatted;
            } else {
                // Fallback to simple replacement for non-ICU strings or on error
                $translation = $this->simpleReplace($translation, $params);
            }
        }

        // Wrap with invisible characters in development mode
        if ($this->isDevelopment && $this->wrapper) {
            $translation = $this->wrapper->wrap($key, $namespace, $translation);
        }

        return $translation;
    }

    private function formatWithICU(string $message, array $params): ?string
    {
        if (!class_exists('MessageFormatter')) {
            return null;
        }

        $formatter = MessageFormatter::create($this->currentLanguage, $message);
        if ($formatter === null) {
            return null;
        }

        $result = $formatter->format($params);
        if ($result === false) {
            return null;
        }

        return $result;
    }

    private function simpleReplace(string $message, array $params): string
    {
        foreach ($params as $paramKey => $paramValue) {
            $message = str_replace('{' . $paramKey . '}', (string)$paramValue, $message);
        }
        return $message;
    }
}
