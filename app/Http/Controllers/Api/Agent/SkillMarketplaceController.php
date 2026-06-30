<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\SkillMarketplaceInstall;
use App\Models\SkillMarketplaceItem;
use App\Services\Agent\SkillRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SkillMarketplaceController extends BaseApiController
{
    public function __construct(
        private readonly SkillRegistry $registry,
    ) {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $query = SkillMarketplaceItem::query()
            ->when($request->query('category'), fn ($q, $c) => $q->where('category', $c))
            ->when($request->query('featured'), fn ($q) => $q->where('is_featured', true))
            ->when($request->query('search'), fn ($q, $s) => $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhere('author', 'like', "%{$s}%");
            }))
            ->when($request->query('min_rating'), fn ($q, $r) => $q->where('rating', '>=', $r))
            ->orderByDesc('downloads_count');

        $items = $query->paginate(min($request->query('per_page', 20), 100));

        return $this->respond([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'total' => $items->total(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $item = SkillMarketplaceItem::withCount('installs')->find($id);
        if (! $item) {
            return $this->respondNotFound();
        }

        return $this->respondSuccess('Marketplace item retrieved', $item);
    }

    public function install(Request $request, int $id): JsonResponse
    {
        $item = SkillMarketplaceItem::find($id);
        if (! $item) {
            return $this->respondNotFound();
        }

        $category = $item->category;
        $skillDir = "skills/{$category}";
        $filename = $item->slug.'.md';
        $fullPath = Storage::path($skillDir.'/'.$filename);

        // Download .md from GitHub
        $content = $this->downloadFromGithub($item->github_url);
        if ($content === null) {
            return $this->respondError('Failed to download skill file from GitHub', 502);
        }

        Storage::makeDirectory($skillDir);
        Storage::put($skillDir.'/'.$filename, $content);

        $item->increment('downloads_count');
        $item->update(['installed_at' => now()]);

        SkillMarketplaceInstall::create([
            'marketplace_item_id' => $item->id,
            'installed_by' => $request->user()->id,
            'installed_at' => now(),
        ]);

        $this->registry->clearCache();

        return $this->respondSuccess('Skill installed successfully', [
            'slug' => $item->slug,
            'path' => $skillDir.'/'.$filename,
        ]);
    }

    public function uninstall(Request $request, int $id): JsonResponse
    {
        $item = SkillMarketplaceItem::find($id);
        if (! $item) {
            return $this->respondNotFound();
        }

        $category = $item->category;
        $path = "skills/{$category}/".$item->slug.'.md';

        if (Storage::exists($path)) {
            Storage::delete($path);
        }

        SkillMarketplaceInstall::where('marketplace_item_id', $item->id)
            ->where('installed_by', $request->user()->id)
            ->whereNull('uninstalled_at')
            ->update(['uninstalled_at' => now()]);

        $item->update(['installed_at' => null]);

        $this->registry->clearCache();

        return $this->respondSuccess('Skill uninstalled successfully');
    }

    public function featured(): JsonResponse
    {
        $items = SkillMarketplaceItem::where('is_featured', true)
            ->withCount('installs')
            ->orderByDesc('rating')
            ->orderByDesc('downloads_count')
            ->limit(20)
            ->get();

        return $this->respondSuccess('Featured marketplace items', $items);
    }

    private function downloadFromGithub(string $githubUrl): ?string
    {
        $rawUrl = $githubUrl;

        // Convert github.com URLs to raw.githubusercontent.com
        if (preg_match('#https?://github\.com/([^/]+)/([^/]+)/blob/(.+)#', $githubUrl, $m)) {
            $rawUrl = "https://raw.githubusercontent.com/{$m[1]}/{$m[2]}/{$m[3]}";
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'method' => 'GET',
                'header' => "User-Agent: TPT-Free-ERP-SkillMarketplace\r\n",
                'ignore_errors' => true,
            ],
        ]);

        $content = @file_get_contents($rawUrl, false, $context);

        if ($content === false) {
            return null;
        }

        return $content;
    }
}
