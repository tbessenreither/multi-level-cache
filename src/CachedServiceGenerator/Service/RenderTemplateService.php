<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service;

use RuntimeException;

class RenderTemplateService
{
    public const string TEMPLATE_DIRECTORY = 'MlcTemplates';
    /**
     * Replace placeholders in the template with provided values.
     * @param string $template
     * @param array<string, string|int> $placeholders
     * @return string
     */
    public static function render(string $templateName, array $placeholders, ?string $templateDirectory = null): string
    {
        $template = self::getTemplate($templateName, $templateDirectory);
        foreach ($placeholders as $key => $value) {
            if (!is_string($value)) {
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif (is_null($value)) {
                    $value = '';
                } else {
                    $value = (string)$value;
                }
            }
            $template = str_replace("/*{{$key}}*/", $value, $template);
        }

        return $template;
    }

    /**
     * Read the cached service template from file.
     */
    private static function getTemplate(string $name, ?string $templateDirectory = null): string
    {
        if ($templateDirectory === null) {
            $templateDirectory = __DIR__ . '/' . self::TEMPLATE_DIRECTORY;
        } else {
            $templateDirectory = rtrim($templateDirectory, '/');
        }
        $templatePath = $templateDirectory . "/{$name}Template.txt";
        if (!file_exists($templatePath)) {
            throw new RuntimeException("Template file not found: {$templatePath}");
        }

        return file_get_contents($templatePath);
    }
}
