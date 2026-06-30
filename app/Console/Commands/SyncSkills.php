<?php

namespace App\Console\Commands;

use App\Services\Agent\SkillRegistry;
use Illuminate\Console\Command;

class SyncSkills extends Command
{
    protected $signature = 'skills:sync {--clear-cache : Clear the skill registry cache}';

    protected $description = 'Sync skill .md files from storage/app/skills/ into the registry cache';

    public function __construct(private readonly SkillRegistry $registry)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        if ($this->option('clear-cache')) {
            $this->registry->clearCache();
            $this->info('Skill registry cache cleared.');
        }

        $skills = $this->registry->all();
        $this->info('Loaded '.count($skills).' skills from storage/app/skills/');

        $this->table(
            ['Slug', 'Category', 'Model Tier', 'Cost Tier', 'Enabled by Default'],
            collect($skills)->map(fn ($s) => [
                $s['slug'],
                $s['category'],
                $s['model_tier'] ?? 'standard',
                $s['cost_tier'] ?? 'medium',
                $s['enabled_by_default'] ? 'yes' : 'no',
            ])->toArray()
        );
    }
}
