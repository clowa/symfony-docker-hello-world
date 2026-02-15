<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use PDO;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PdoItemController
{
    private PDO $pdo;

    public function __construct(Connection $connection)
    {
        $nativeConnection = $connection->getNativeConnection();

        if (!$nativeConnection instanceof PDO) {
            throw new \RuntimeException('Database connection is not a PDO instance.');
        }

        $this->pdo = $nativeConnection;
    }

    #[Route('/api/items', name: 'api_items_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $this->ensureTable();

        $page = $this->getQueryInt($request, 'page', 1);
        $perPage = $this->getQueryInt($request, 'per_page', 100);

        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1) {
            $perPage = 1;
        } elseif ($perPage > 1000) {
            $perPage = 1000;
        }

        $offset = ($page - 1) * $perPage;

        $countStatement = $this->pdo->query('SELECT COUNT(*) FROM app_items');
        $total = (int) $countStatement->fetchColumn();

        $statement = $this->pdo->prepare(
            'SELECT id, name, created_at, updated_at FROM app_items ORDER BY id DESC LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue('limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return new JsonResponse([
            'data' => $statement->fetchAll(PDO::FETCH_ASSOC),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
            ],
        ]);
    }

    #[Route('/api/items', name: 'api_items_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->ensureTable();

        $data = $this->decodeJson($request);
        $name = isset($data['name']) ? trim((string) $data['name']) : '';

        if ($name == '') {
            return new JsonResponse(['error' => 'name is required'], 400);
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO app_items (name) VALUES (:name) RETURNING id, name, created_at, updated_at'
        );
        $statement->execute(['name' => $name]);

        $item = $statement->fetch(PDO::FETCH_ASSOC);

        return new JsonResponse($item, 201);
    }

    #[Route('/api/items/{id}', name: 'api_items_get', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $this->ensureTable();

        $statement = $this->pdo->prepare(
            'SELECT id, name, created_at, updated_at FROM app_items WHERE id = :id'
        );
        $statement->execute(['id' => $id]);

        $item = $statement->fetch(PDO::FETCH_ASSOC);

        if ($item === false) {
            return new JsonResponse(['error' => 'not found'], 404);
        }

        return new JsonResponse($item);
    }

    #[Route('/api/items/{id}', name: 'api_items_update', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $this->ensureTable();

        $data = $this->decodeJson($request);
        $name = isset($data['name']) ? trim((string) $data['name']) : '';

        if ($name == '') {
            return new JsonResponse(['error' => 'name is required'], 400);
        }

        $statement = $this->pdo->prepare(
            'UPDATE app_items SET name = :name, updated_at = NOW() WHERE id = :id
            RETURNING id, name, created_at, updated_at'
        );
        $statement->execute(['id' => $id, 'name' => $name]);

        $item = $statement->fetch(PDO::FETCH_ASSOC);

        if ($item === false) {
            return new JsonResponse(['error' => 'not found'], 404);
        }

        return new JsonResponse($item);
    }

    #[Route('/api/items/{id}', name: 'api_items_delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->ensureTable();

        $statement = $this->pdo->prepare('DELETE FROM app_items WHERE id = :id');
        $statement->execute(['id' => $id]);

        if ($statement->rowCount() === 0) {
            return new JsonResponse(['error' => 'not found'], 404);
        }

        return new JsonResponse(null, 204);
    }

    private function ensureTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS app_items (
                id SERIAL PRIMARY KEY,
                name TEXT NOT NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            )'
        );
    }

    private function decodeJson(Request $request): array
    {
        $payload = trim($request->getContent());

        if ($payload === '') {
            return [];
        }

        $data = json_decode($payload, true);

        return is_array($data) ? $data : [];
    }

    private function getQueryInt(Request $request, string $key, int $default): int
    {
        $value = $request->query->get($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return (int) $value;
    }
}
