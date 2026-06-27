<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Article;
use App\Models\ArticleDetail;
use App\Services\ServiceFactory;

/**
 * Articles controller.
 */
final class ArticleController extends Controller
{
    public function index(Request $request): void
    {
        $response = ServiceFactory::articleService()->getAllArticles('');
        $apiHttpCode = (int) ($response['http_code'] ?? 200);

        if ($apiHttpCode === 401 || $apiHttpCode === 406) {
            ServiceFactory::authService()->logout();
            flash('danger', (string) ($response['message'] ?? 'Sesion expirada.'));
            $this->redirect('login');
            return;
        }

        $rows = $apiHttpCode === 200 && is_array($response['data'] ?? null) ? $response['data'] : [];
        $articles = array_map(static fn (array $row): Article => Article::fromArray($row), $rows);

        $this->view('articles/index', [
            'title' => 'Articulos',
            'articles' => $articles,
            'apiHttpCode' => $apiHttpCode,
            'authUser' => ServiceFactory::sessionManager()->getUser(),
            'csrfToken' => get_csrf_token(),
            'flashMessages' => consume_flash(),
        ]);
    }

    public function list(Request $request): void
    {
        $search = sanitize_text((string) $request->input('search', ''));
        $response = ServiceFactory::articleService()->getAllArticles($search);
        $this->json($response);
    }

    public function detail(Request $request): void
    {
        $search = sanitize_text((string) $request->input('search', ''));
        if ($search === '') {
            $this->json([
                'code' => '422',
                'message' => 'ID de articulo requerido',
                'data' => null,
            ], 422);
        }

        $response = ServiceFactory::articleService()->getArticleDetail($search);

        $detailPayload = is_array($response['data'] ?? null) ? $response['data'] : [];
        $detail = ArticleDetail::fromArray($detailPayload);

        $this->json([
            'code' => (string) ($response['code'] ?? ''),
            'message' => (string) ($response['message'] ?? ''),
            'data' => [
                'Article' => $detail->Article === null ? null : [
                    'ID' => $detail->Article->ID,
                    'DESCRIPTION' => $detail->Article->DESCRIPTION,
                    'PRICE' => $detail->Article->PRICE,
                    'NOTAS' => $detail->Article->NOTAS,
                ],
                'Pictures' => array_map(static fn ($picture): array => [
                    'picture' => $picture->picture,
                    'ext' => $picture->ext,
                    'dataUri' => $picture->toDataUri(),
                ], $detail->Pictures),
                'Stocks' => array_map(static fn ($stock): array => [
                    'NAME' => $stock->NAME,
                    'AVAILABLE' => $stock->AVAILABLE,
                ], $detail->Stocks),
            ],
        ], (int) ($response['http_code'] ?? 200));
    }
}
