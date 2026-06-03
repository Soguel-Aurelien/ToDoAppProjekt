<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\TodoContactModel;
use App\Models\TodoModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\HTTP\ResponseInterface;

class CategoriesController extends BaseController
{
    private CategoryModel $categories;
    private TodoModel $todos;
    private TodoContactModel $contacts;

    private array $categoryOrderFields = [
        'id' => 'id',
        'name' => 'name',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
    ];

    public function __construct()
    {
        $this->categories = new CategoryModel();
        $this->todos = new TodoModel();
        $this->contacts = new TodoContactModel();
    }

    public function index(): ResponseInterface
    {
        $pagination = $this->getPagination();
        if (isset($pagination['error'])) {
            return $this->fail($pagination['error']);
        }

        $orderBy = $this->request->getGet('order_by') ?? 'id';
        if (! isset($this->categoryOrderFields[$orderBy])) {
            return $this->fail('Ungueltiger Sortierparameter. Erlaubt sind: id, name, created_at, updated_at.');
        }

        $direction = strtolower((string) ($this->request->getGet('direction') ?? 'asc'));
        if (! in_array($direction, ['asc', 'desc'], true)) {
            return $this->fail('Ungueltige Sortierrichtung. Erlaubt sind: asc oder desc.');
        }

        $builder = $this->categories
            ->orderBy($this->categoryOrderFields[$orderBy], $direction);

        $total = $builder->countAllResults(false);
        $rows = $builder
            ->findAll($pagination['limit'], ($pagination['page'] - 1) * $pagination['limit']);

        $data = array_map(fn (array $row): array => $this->formatCategory($row), $rows);

        if ((string) $this->request->getGet('include_todos') === '1') {
            $data = $this->attachTodos($data);
        }

        return $this->respond([
            'data' => $data,
            'meta' => [
                'page' => $pagination['page'],
                'limit' => $pagination['limit'],
                'total' => $total,
            ],
        ]);
    }

    public function show($id = null): ResponseInterface
    {
        $id = $this->validateId($id);
        if ($id === null) {
            return $this->fail('Ungueltige Kategorie-ID in der URL.');
        }

        $category = $this->categories->find($id);
        if ($category === null) {
            return $this->fail('Kategorie wurde nicht gefunden.', 404);
        }

        $data = $this->attachTodos([$this->formatCategory($category)])[0];

        return $this->respond(['data' => $data]);
    }

    public function create(): ResponseInterface
    {
        $payload = $this->getJsonPayload();
        if ($payload === null) {
            return $this->fail('Ungueltige JSON-Daten.');
        }

        $validation = $this->validateCategoryPayload($payload, true);
        if ($validation !== []) {
            return $this->fail('Die Eingabedaten sind ungueltig.', 422, $validation);
        }

        try {
            $id = $this->categories->insert($this->categoryData($payload, true), true);
        } catch (DatabaseException $exception) {
            return $this->fail('Datenbank ist nicht erreichbar. Bitte MySQL starten und Migrationen ausfuehren.', 503, [
                'database' => 'Verbindung zu localhost:3306 / todo_app fehlgeschlagen.',
            ]);
        }

        return $this->respond([
            'message' => 'Kategorie wurde erstellt.',
            'data' => $this->formatCategory($this->categories->find($id)),
        ], 201);
    }

    public function update($id = null): ResponseInterface
    {
        $id = $this->validateId($id);
        if ($id === null) {
            return $this->fail('Ungueltige Kategorie-ID in der URL.');
        }

        if ($this->categories->find($id) === null) {
            return $this->fail('Kategorie wurde nicht gefunden.', 404);
        }

        $payload = $this->getJsonPayload();
        if ($payload === null) {
            return $this->fail('Ungueltige JSON-Daten.');
        }

        $validation = $this->validateCategoryPayload($payload, false);
        if ($validation !== []) {
            return $this->fail('Die Eingabedaten sind ungueltig.', 422, $validation);
        }

        $this->categories->update($id, $this->categoryData($payload));

        return $this->respond([
            'message' => 'Kategorie wurde aktualisiert.',
            'data' => $this->formatCategory($this->categories->find($id)),
        ]);
    }

    public function delete($id = null): ResponseInterface
    {
        $id = $this->validateId($id);
        if ($id === null) {
            return $this->fail('Ungueltige Kategorie-ID in der URL.');
        }

        if ($this->categories->find($id) === null) {
            return $this->fail('Kategorie wurde nicht gefunden.', 404);
        }

        if ($this->todos->where('domain_id', $id)->countAllResults() > 0) {
            return $this->fail('Kategorie kann nicht geloescht werden, solange TODO-Aufgaben darin vorhanden sind.', 409, [
                'category_id' => 'Verschieben oder loeschen Sie zuerst alle TODO-Aufgaben dieser Kategorie.',
            ]);
        }

        $this->categories->delete($id);

        return $this->respondDeleted(['message' => 'Kategorie wurde geloescht.']);
    }

    public function unlock($id = null): ResponseInterface
    {
        $id = $this->validateId($id);
        if ($id === null) {
            return $this->fail('Ungueltige Kategorie-ID in der URL.');
        }

        $category = $this->categories->find($id);
        if ($category === null) {
            return $this->fail('Kategorie wurde nicht gefunden.', 404);
        }

        $payload = $this->getJsonPayload();
        if ($payload === null || ! isset($payload['password'])) {
            return $this->fail('Bitte ein Passwort als JSON senden.');
        }

        if ((int) $category['is_protected'] !== 1) {
            return $this->respond(['message' => 'Kategorie ist nicht geschuetzt.']);
        }

        if (! password_verify((string) $payload['password'], (string) $category['password_hash'])) {
            return $this->fail('Falsches Passwort.', 403);
        }

        return $this->respond(['message' => 'Kategorie wurde entsperrt.']);
    }

    private function attachTodos(array $categories): array
    {
        foreach ($categories as &$category) {
            $todos = $this->todos
                ->where('domain_id', $category['id'])
                ->orderBy('id', 'asc')
                ->findAll();

            $category['toDos'] = array_map(fn (array $todo): array => $this->formatTodo($todo), $todos);
        }

        return $categories;
    }

    private function formatCategory(array $category): array
    {
        return [
            'id' => (int) $category['id'],
            'name' => $category['name'],
            'description' => $category['description'] ?? '',
            'isProtected' => (bool) ($category['is_protected'] ?? false),
            'isUnlocked' => ! (bool) ($category['is_protected'] ?? false),
            'createdAt' => $category['created_at'] ?? null,
            'updatedAt' => $category['updated_at'] ?? null,
        ];
    }

    private function formatTodo(array $todo): array
    {
        $emails = $this->contacts
            ->where('todo_id', $todo['id'])
            ->findColumn('email') ?? [];

        return [
            'id' => (int) $todo['id'],
            'categoryId' => (int) $todo['domain_id'],
            'title' => $todo['title'],
            'description' => $todo['description'],
            'contactEmail' => implode(', ', $emails),
            'endDate' => $todo['end_date'],
            'priority' => $todo['priority'],
            'status' => $todo['status'],
            'createdAt' => $todo['created_at'] ?? null,
            'updatedAt' => $todo['updated_at'] ?? null,
        ];
    }

    private function categoryData(array $payload, bool $creating = false): array
    {
        $isProtected = $this->toBoolean($payload['is_protected'] ?? $payload['isProtected'] ?? false);
        $data = [];

        if ($creating || array_key_exists('name', $payload)) {
            $data['name'] = trim((string) ($payload['name'] ?? ''));
        }

        if ($creating || array_key_exists('description', $payload)) {
            $data['description'] = trim((string) ($payload['description'] ?? ''));
        }

        if ($creating || array_key_exists('is_protected', $payload) || array_key_exists('isProtected', $payload)) {
            $data['is_protected'] = $isProtected ? 1 : 0;
        }

        if ($isProtected && isset($payload['password']) && trim((string) $payload['password']) !== '') {
            $data['password_hash'] = password_hash((string) $payload['password'], PASSWORD_DEFAULT);
        } elseif ($creating) {
            $data['password_hash'] = null;
        }

        return $data;
    }

    private function validateCategoryPayload(array $payload, bool $creating): array
    {
        $errors = [];
        $name = trim((string) ($payload['name'] ?? ''));
        $isProtected = $this->toBoolean($payload['is_protected'] ?? $payload['isProtected'] ?? false);
        $password = trim((string) ($payload['password'] ?? ''));

        if (! $creating && ! $this->hasAnyKnownField($payload, ['name', 'description', 'is_protected', 'isProtected', 'password'])) {
            $errors['payload'] = 'Mindestens ein Feld muss gesendet werden.';
        }

        if (($creating || array_key_exists('name', $payload)) && $name === '') {
            $errors['name'] = 'Name ist erforderlich.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'Name darf maximal 100 Zeichen lang sein.';
        }

        if (($creating || array_key_exists('description', $payload)) && mb_strlen(trim((string) ($payload['description'] ?? ''))) > 1000) {
            $errors['description'] = 'Beschreibung darf maximal 1000 Zeichen lang sein.';
        }

        if (($creating || array_key_exists('is_protected', $payload) || array_key_exists('isProtected', $payload)) && ! $this->isBooleanLike($payload['is_protected'] ?? $payload['isProtected'] ?? false)) {
            $errors['is_protected'] = 'isProtected muss true oder false sein.';
        }

        if ($isProtected && ($creating || array_key_exists('password', $payload)) && $password === '') {
            $errors['password'] = 'Passwort ist fuer geschuetzte Kategorien erforderlich.';
        }

        return $errors;
    }

    private function isBooleanLike(mixed $value): bool
    {
        return is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true);
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array($value, [1, '1', 'true'], true);
    }

    private function hasAnyKnownField(array $payload, array $fields): bool
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }

    private function getJsonPayload(): ?array
    {
        $payload = $this->request->getJSON(true);

        return is_array($payload) ? $payload : null;
    }

    private function getPagination(): array
    {
        $limit = (int) ($this->request->getGet('limit') ?? 50);
        $page = (int) ($this->request->getGet('page') ?? 1);

        if ($limit < 1 || $limit > 100) {
            return ['error' => 'Limit muss zwischen 1 und 100 liegen.'];
        }

        if ($page < 1) {
            return ['error' => 'Page muss groesser oder gleich 1 sein.'];
        }

        return ['limit' => $limit, 'page' => $page];
    }

    private function validateId($id): ?int
    {
        return filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }

    private function respond(array $body, int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON($body);
    }

    private function respondDeleted(array $body): ResponseInterface
    {
        return $this->respond($body);
    }

    private function fail(string $message, int $status = 400, array $errors = []): ResponseInterface
    {
        return $this->respond([
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
