<?php

namespace App\Services;

use ReflectionClass;

class McpDiscoveryService
{
    /**
     * Discover all registered MCP servers by parsing routes/ai.php.
     *
     * @return array<int, array{
     *     handle: string,
     *     class: class-string,
     *     name: string,
     *     version: string,
     *     instructions: string,
     *     tools: array<int, array{class: string, name: string, description: string}>,
     *     resources: array<int, array{class: string, name: string, description: string}>,
     *     prompts: array<int, array{class: string, name: string, description: string}>,
     * }>
     */
    public function discover(): array
    {
        $servers = [];

        $aiRoutesPath = base_path('routes/ai.php');

        if (! file_exists($aiRoutesPath)) {
            return $servers;
        }

        $content = file_get_contents($aiRoutesPath);

        // Match: Mcp::web("handle", ServerClass::class) or Mcp::web('handle', ServerClass::class)
        preg_match_all(
            '/Mcp::web\s*\(\s*["\']([^"\']+)["\']\s*,\s*([\w\\\\]+)::class\s*\)/',
            $content,
            $matches,
            PREG_SET_ORDER,
        );

        foreach ($matches as $match) {
            $handle = $match[1];
            $serverClass = $match[2];

            if (! class_exists($serverClass)) {
                continue;
            }

            $servers[] = $this->extractServerMetadata($handle, $serverClass);
        }

        return $servers;
    }

    /**
     * @param  class-string  $serverClass
     * @return array{
     *     handle: string,
     *     class: class-string,
     *     name: string,
     *     version: string,
     *     instructions: string,
     *     tools: array<int, array{class: string, name: string, description: string}>,
     *     resources: array<int, array{class: string, name: string, description: string}>,
     *     prompts: array<int, array{class: string, name: string, description: string}>,
     * }
     */
    private function extractServerMetadata(string $handle, string $serverClass): array
    {
        $reflection = new ReflectionClass($serverClass);

        $name = $this->getProtectedProperty($reflection, 'name') ?? 'Unknown Server';
        $version = $this->getProtectedProperty($reflection, 'version') ?? '0.0.1';
        $instructions = trim((string) ($this->getProtectedProperty($reflection, 'instructions') ?? ''));
        $toolClasses = $this->getProtectedProperty($reflection, 'tools') ?? [];
        $resourceClasses = $this->getProtectedProperty($reflection, 'resources') ?? [];
        $promptClasses = $this->getProtectedProperty($reflection, 'prompts') ?? [];

        return [
            'handle' => $handle,
            'class' => $serverClass,
            'name' => $name,
            'version' => $version,
            'instructions' => $instructions,
            'tools' => $this->extractComponentMetadata($toolClasses),
            'resources' => $this->extractComponentMetadata($resourceClasses),
            'prompts' => $this->extractComponentMetadata($promptClasses),
        ];
    }

    /**
     * @param  array<int, class-string>  $classes
     * @return array<int, array{class: string, name: string, description: string}>
     */
    private function extractComponentMetadata(array $classes): array
    {
        $result = [];

        foreach ($classes as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            $shortName = $reflection->getShortName();
            $description = trim((string) ($this->getProtectedProperty($reflection, 'description') ?? ''));

            $result[] = [
                'class' => $shortName,
                'name' => $this->classNameToLabel($shortName),
                'description' => $description,
            ];
        }

        return $result;
    }

    private function getProtectedProperty(ReflectionClass $reflection, string $property): mixed
    {
        if (! $reflection->hasProperty($property)) {
            return null;
        }

        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);

        // Get default value without instantiating (works for typed defaults)
        if ($prop->hasDefaultValue()) {
            return $prop->getDefaultValue();
        }

        return null;
    }

    private function classNameToLabel(string $className): string
    {
        // Strip common suffixes then convert PascalCase to words
        $name = preg_replace('/Tool$|Resource$|Prompt$/', '', $className) ?? $className;

        return trim((string) preg_replace('/([A-Z])/', ' $1', $name));
    }
}
