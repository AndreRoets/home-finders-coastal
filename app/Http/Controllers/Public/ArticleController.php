<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Corex\AgentMapper;
use App\Services\Corex\ArticleMapper;
use App\Services\Corex\CorexClient;
use App\Support\DynamicSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ArticleController extends Controller
{
    public function __construct(protected CorexClient $corex) {}

    /**
     * Full article page (/articles/{slug}).
     *
     * CoreX exposes articles by id, so we resolve the slug against the agency's
     * published articles, then fetch the single article for the freshest body
     * and the author for the by-line / profile card. Renders a graceful 404
     * when the slug matches no published article.
     */
    public function show(Request $request, string $slug): HttpResponse
    {
        $match = $this->findBySlug($slug);

        if ($match === []) {
            return Inertia::render('article', ['article' => null, 'agent' => null])
                ->toResponse($request)
                ->setStatusCode(HttpResponse::HTTP_NOT_FOUND);
        }

        $article = $this->corex->article($match['id']);

        if ($article === []) {
            $article = $match;
        }

        $mappedArticle = ArticleMapper::map($article);

        View::share('seoPage', DynamicSeo::forArticle($mappedArticle, route('articles.show', $mappedArticle['slug'] ?: $slug)));

        return Inertia::render('article', [
            'article' => $mappedArticle,
            'agent' => $this->resolveAgent(Arr::get($article, 'agent_id')),
        ])->toResponse($request);
    }

    /**
     * Find the published article whose slug matches $slug, scanning the agency's
     * articles collection (cached). Returns the raw row, or [] when none match.
     *
     * @return array<string, mixed>
     */
    protected function findBySlug(string $slug): array
    {
        foreach ($this->corex->articles() as $article) {
            if ((string) Arr::get($article, 'slug') === $slug) {
                return $article;
            }
        }

        return [];
    }

    /**
     * The article's author for the by-line and profile card. Falls back to the
     * agent embedded on the author's listings when the standalone /agents/{id}
     * endpoint isn't syndicated, mirroring AgentController.
     *
     * @return array<string, mixed>|null
     */
    protected function resolveAgent(int|string|null $agentId): ?array
    {
        if ($agentId === null || $agentId === '') {
            return null;
        }

        $agent = $this->corex->agent($agentId);

        if ($agent === []) {
            foreach ($this->corex->listings(['agent_id' => $agentId]) as $listing) {
                $embedded = Arr::get($listing, 'agent');

                if (is_array($embedded) && (string) Arr::get($embedded, 'id') === (string) $agentId) {
                    $agent = $embedded;
                    break;
                }
            }
        }

        return $agent !== [] ? AgentMapper::map($agent) : null;
    }
}
