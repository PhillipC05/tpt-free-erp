<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SkillRegistry
{
    private const CACHE_KEY = 'skill_registry_v1';

    private const CACHE_TTL = 3600; // 1 hour

    private string $skillsPath;

    public function __construct()
    {
        $this->skillsPath = storage_path('app/skills');
    }

    public function all(): array
    {
        if (! File::isDirectory($this->skillsPath) || empty(File::allFiles($this->skillsPath))) {
            Log::info('SkillRegistry: storage/app/skills/ directory is empty or missing — returning empty skill list.');

            return [];
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->scanAll());
    }

    public function getSkillsHelpMessage(): string
    {
        return 'To add skills, create Markdown (.md) files with YAML frontmatter in storage/app/skills/{category}/. '
            .'Each file must include at minimum: slug, category, name, description. '
            .'Example: storage/app/skills/mycategory/my-skill.md';
    }

    public function find(string $slug): ?array
    {
        return collect($this->all())->firstWhere('slug', $slug);
    }

    public function byCategory(string $category): array
    {
        return collect($this->all())->where('category', $category)->values()->toArray();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Parse a raw markdown string (with YAML frontmatter) and return the skill metadata.
     * Returns an array with frontmatter fields plus 'instructions', or null on parse failure.
     */
    public function parseContent(string $content): ?array
    {
        if (! preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)/s', $content, $matches)) {
            return null;
        }

        $yamlStr = $matches[1];
        $markdown = trim($matches[2]);

        try {
            $meta = $this->parseYaml($yamlStr);
        } catch (\Throwable $e) {
            return null;
        }

        return array_merge($meta, ['instructions' => $markdown]);
    }

    private function scanAll(): array
    {
        if (! File::isDirectory($this->skillsPath)) {
            return [];
        }

        $skills = [];
        $files = File::allFiles($this->skillsPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $skill = $this->parseSkillFile($file->getPathname());
            if ($skill) {
                $skills[] = $skill;
            }
        }

        // Sort by category then slug
        usort($skills, fn ($a, $b) => strcmp($a['category'].'.'.$a['slug'], $b['category'].'.'.$b['slug']));

        return $skills;
    }

    private function parseSkillFile(string $path): ?array
    {
        $content = File::get($path);

        // Extract YAML frontmatter between --- delimiters
        if (! preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)/s', $content, $matches)) {
            return null;
        }

        $yamlStr = $matches[1];
        $markdown = trim($matches[2]);

        try {
            $meta = $this->parseYaml($yamlStr);
        } catch (\Throwable $e) {
            return null;
        }

        if (empty($meta['slug'])) {
            return null;
        }

        return array_merge($meta, [
            'instructions' => $markdown,
            'file_path' => $path,
        ]);
    }

    /**
     * Minimal YAML parser for skill frontmatter.
     * Handles strings, integers, booleans, and simple lists.
     */
    private function parseYaml(string $yaml): array
    {
        $result = [];
        $lines = explode("\n", $yaml);
        $i = 0;
        $n = count($lines);

        while ($i < $n) {
            $line = $lines[$i];

            // Top-level key: value
            if (preg_match('/^(\w[\w_-]*)\s*:\s*(.*)$/', $line, $m)) {
                $key = $m[1];
                $value = trim($m[2]);

                if ($value === '' || $value === null) {
                    // Could be a block list — peek at next lines
                    $list = [];
                    while (isset($lines[$i + 1]) && preg_match('/^\s+-\s+(.+)$/', $lines[$i + 1], $lm)) {
                        $list[] = trim($lm[1]);
                        $i++;
                    }
                    $result[$key] = empty($list) ? null : $list;
                } elseif (preg_match('/^\[(.+)\]$/', $value, $lm)) {
                    // Inline list [a, b, c]
                    $result[$key] = array_map('trim', explode(',', $lm[1]));
                } else {
                    $result[$key] = $this->castYamlValue($value);
                }
            }

            $i++;
        }

        return $result;
    }

    private function castYamlValue(string $value): mixed
    {
        // Remove quotes
        if (preg_match('/^["\'](.+)["\']$/', $value, $m)) {
            return $m[1];
        }
        // Booleans
        if (in_array(strtolower($value), ['true', 'yes'])) {
            return true;
        }
        if (in_array(strtolower($value), ['false', 'no'])) {
            return false;
        }
        // Null
        if (in_array(strtolower($value), ['null', '~', ''])) {
            return null;
        }
        // Integer
        if (ctype_digit($value)) {
            return (int) $value;
        }
        // Float
        if (is_numeric($value)) {
            return (float) $value;
        }

        return $value;
    }
}
