<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service;

class MlcRenderTemplateService
{
    /**
     * Replace placeholders in the template with provided values.
     * @param string $template
     * @param array<string, string|int> $placeholders
     * @return string
     */
    public static function render(string $templateName, array $placeholders): string
    {
        $template = self::getTemplate($templateName);
        foreach ($placeholders as $key => $value) {
            if($value === null || $value === false) {
                $value = '';
            }
            if(!is_string($value)) {
                $value = (string)$value;
            }
            $template = str_replace("/*{{$key}}*/", $value, $template);
        }
        return $template;
    }

    /**
     * Read the cached service template from file.
     */
    private static function getTemplate(string $name): string
    {
        $templatePath = __DIR__ . "/MlcTemplates/{$name}Template.txt";
        return file_get_contents($templatePath);
    }
}
