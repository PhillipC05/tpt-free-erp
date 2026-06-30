<?php

namespace Tests\Feature\Agent;

use App\Services\Agent\SkillRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SkillRegistryTest extends TestCase
{
    use RefreshDatabase;

    private SkillRegistry $registry;

    private string $skillsDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = app(SkillRegistry::class);
        $this->skillsDir = storage_path('app/skills');
        $this->registry->clearCache();
    }

    protected function tearDown(): void
    {
        // Clean up test skill files recursively
        $testDir = $this->skillsDir.'/test';
        if (is_dir($testDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($testDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($testDir);
        }
        $this->registry->clearCache();
        parent::tearDown();
    }

    private function writeSkillFile(string $category, string $slug, array $overrides = []): string
    {
        $dir = $this->skillsDir.'/'.$category;
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $defaults = [
            'name' => 'Test Skill',
            'slug' => $slug,
            'version' => '"1.0"',
            'category' => $category,
            'description' => 'A test skill for unit testing.',
            'required_permissions' => ['finance.view'],
            'affected_modules' => ['finance'],
            'model_tier' => 'fast',
            'estimated_tokens' => 500,
            'cost_tier' => 'low',
            'enabled_by_default' => false,
            'tags' => ['test'],
        ];
        $meta = array_merge($defaults, $overrides);

        $yaml = "---\n";
        foreach ($meta as $key => $value) {
            if (is_array($value)) {
                $yaml .= "{$key}:\n";
                foreach ($value as $item) {
                    $yaml .= "  - {$item}\n";
                }
            } elseif (is_bool($value)) {
                $yaml .= "{$key}: ".($value ? 'true' : 'false')."\n";
            } else {
                $yaml .= "{$key}: {$value}\n";
            }
        }
        $yaml .= "---\n\n## Task\n\nReturn JSON: {\"result\": \"ok\"}\n";

        $path = $dir.'/'.str_replace('.', '_', $slug).'.md';
        file_put_contents($path, $yaml);

        return $path;
    }

    public function test_all_returns_array(): void
    {
        $skills = $this->registry->all();
        $this->assertIsArray($skills);
    }

    public function test_parses_skill_file_correctly(): void
    {
        $this->writeSkillFile('test', 'test.test_skill');
        $this->registry->clearCache();

        $skills = $this->registry->all();
        $skill = collect($skills)->firstWhere('slug', 'test.test_skill');

        $this->assertNotNull($skill);
        $this->assertEquals('Test Skill', $skill['name']);
        $this->assertEquals('test', $skill['category']);
        $this->assertEquals('fast', $skill['model_tier']);
        $this->assertEquals('low', $skill['cost_tier']);
        $this->assertFalse($skill['enabled_by_default']);
        $this->assertStringContainsString('Return JSON', $skill['instructions']);
    }

    public function test_find_returns_skill_by_slug(): void
    {
        $this->writeSkillFile('test', 'test.test_skill');
        $this->registry->clearCache();

        $skill = $this->registry->find('test.test_skill');

        $this->assertNotNull($skill);
        $this->assertEquals('test.test_skill', $skill['slug']);
    }

    public function test_find_returns_null_for_unknown_slug(): void
    {
        $skill = $this->registry->find('does.not_exist');
        $this->assertNull($skill);
    }

    public function test_by_category_filters_correctly(): void
    {
        $this->writeSkillFile('test', 'test.skill_one');
        $this->registry->clearCache();

        $skills = $this->registry->byCategory('test');
        $this->assertNotEmpty($skills);
        foreach ($skills as $skill) {
            $this->assertEquals('test', $skill['category']);
        }
    }

    public function test_skills_are_cached(): void
    {
        $this->writeSkillFile('test', 'test.test_skill');
        $this->registry->clearCache();

        // Load once to warm cache
        $this->registry->all();
        $this->assertTrue(Cache::has('skill_registry_v1'));
    }

    public function test_clear_cache_removes_cached_skills(): void
    {
        $this->writeSkillFile('test', 'test.test_skill');
        $this->registry->all(); // warm cache
        $this->registry->clearCache();

        $this->assertFalse(Cache::has('skill_registry_v1'));
    }

    public function test_real_skill_files_are_parseable(): void
    {
        // Verify the actual Tier 1 skills we shipped can be parsed
        $skills = $this->registry->all();

        // Should have at least the 10 Tier 1 skills we created
        $this->assertGreaterThanOrEqual(10, count($skills));

        $slugs = array_column($skills, 'slug');
        $this->assertContains('finance.extract_invoice', $slugs);
        $this->assertContains('sales.score_crm_lead', $slugs);
        $this->assertContains('hr.draft_job_description', $slugs);
    }

    public function test_skill_without_frontmatter_is_skipped(): void
    {
        $dir = $this->skillsDir.'/test';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir.'/invalid.md', "# No frontmatter here\n\nJust a plain markdown file.\n");
        $this->registry->clearCache();

        $skills = $this->registry->all();
        $invalid = collect($skills)->firstWhere('slug', null);
        $this->assertNull($invalid);
    }
}
