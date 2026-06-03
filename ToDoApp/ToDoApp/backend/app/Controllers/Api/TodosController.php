<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\TodoContactModel;
use App\Models\TodoModel;
use CodeIgniter\HTTP\ResponseInterface;

class TodosController extends BaseController
{
    private CategoryModel $categories;
    private TodoModel $todos;
    private TodoContactModel $contacts;

    private array $todoOrderFields = [
        'id' => 'todos.id',
        'title' => 'todos.title',
        'date' => 'todos.end_date',
        'end_date' => 'todos.end_date',
        'priority' => 'todos.priority',
        'status' => 'todos.status',
        'created_at' => 'todos.created_at',
        'updated_at' => 'todos.updated_at',
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

        $categoryId = $this->request->getGet('category_id');
        if ($categoryId !== null && $this->validateId($categoryId) === null) {
            return $this->fail('Ungueltige category_id in der URL.');
        }
        if ($categoryId !== null && $this->categories->find((int) $categoryId) === null) {
            return $this->fail('Kategorie fuer category_id wurde nicht gefunden.', 404);
        }

        $orderBy = $this->request->getGet('order_by') ?? 'id';
        if (! isset($this->todoOrderFields[$orderBy])) {
            return $this->fail('Ungueltiger Sortierparameter. Erlaubt sind: id, title, date, end_date, priority, status, created_at, updated_at.');
        }

        $direction = strtolower((string) ($this->request->getGet('direction') ?? 'asc'));
        if (! in_array($direction, ['asc', 'desc'], true)) {
            return $this->fail('Ungueltige Sortierrichtung. Erlaubt sind: asc oder desc.');
        }

        $builder = $this->todos
            ->select('todos.*, domains.name AS category_name')
            ->join('domains', 'domains.id = todos.domain_id', 'left');

        if ($categoryId !== null) {
            $builder->where('todos.domain_id', (int) $categoryId);
        }

        $total = $builder->countAllResults(false);
        $rows = $builder
            ->orderBy($this->todoOrderFields[$orderBy], $direction)
            ->findAll($pagination['limit'], ($pagination['page'] - 1) * $pagination['limit']);

        return $this->respond([
            'data' => array_map(fn (array $row): array => $this->formatTodo($row), $rows),
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
            return $this->fail('Ungueltige TODO-ID in der URL.');
        }

        $todo = $this->todos
            ->select('todos.*, domains.name AS category_name')
            ->join('domains', 'domains.id = todos.domain_id', 'left')
            ->find($id);

        if ($todo === null) {
            return $this->fail('TODO wurde nicht gefunden.', 404);
        }

        return $this->respond(['data' => $this->formatTodo($todo)]);
    }

    public function create(): ResponseInterface
    {
        $payload = $this->getJsonPayload();
        if ($payload === null) {
            return $this->fail('Ungueltige JSON-Daten.');
        }

        $validation = $this->validateTodoPayload($payload, true);
        if ($validation !== []) {
            return $this->fail('Die Eingabedaten sind ungueltig.', 422, $validation);
        }

        $id = $this->todos->insert($this->todoData($payload, true), true);
        $this->replaceContacts($id, $payload['contact_emails'] ?? $payload['contactEmails'] ?? $payload['contactEmail'] ?? []);

        return $this->respond([
            'message' => 'TODO wurde erstellt.',
            'data' => $this->loadTodo($id),
        ], 201);
    }

    public function update($id = null): ResponseInterface
    {
        $id = $this->validateId($id);
        if ($id === null) {
            return $this->fail('Ungueltige TODO-ID in der URL.');
        }

        if ($this->todos->find($id) === null) {
            return $this->fail('TODO wurde nicht gefunden.', 404);
        }

        $payload = $this->getJsonPayload();
        if ($payload === null) {
            return $this->fail('Ungueltige JSON-Daten.');
        }

        $validation = $this->validateTodoPayload($payload, false);
        if ($validation !== []) {
            return $this->fail('Die Eingabedaten sind ungueltig.', 422, $validation);
        }

        $this->todos->update($id, $this->todoData($payload));

        if (array_key_exists('contact_emails', $payload) || array_key_exists('contactEmails', $payload) || array_key_exists('contactEmail', $payload)) {
            $this->replaceContacts($id, $payload['contact_emails'] ?? $payload['contactEmails'] ?? $payload['contactEmail'] ?? []);
        }

        return $this->respond([
            'message' => 'TODO wurde aktualisiert.',
            'data' => $this->loadTodo($id),
        ]);
    }

    public function delete($id = null): ResponseInterface
    {
        $id = $this->validateId($id);
        if ($id === null) {
            return $this->fail('Ungueltige TODO-ID in der URL.');
        }

        if ($this->todos->find($id) === null) {
            return $this->fail('TODO wurde nicht gefunden.', 404);
        }

        $this->contacts->where('todo_id', $id)->delete();
        $this->todos->delete($id);

        return $this->respond(['message' => 'TODO wurde geloescht.']);
    }

    private function loadTodo(int $id): array
    {
        $todo = $this->todos
            ->select('todos.*, domains.name AS category_name')
            ->join('domains', 'domains.id = todos.domain_id', 'left')
            ->find($id);

        return $this->formatTodo($todo);
    }

    private function formatTodo(array $todo): array
    {
        $emails = $this->contacts
            ->where('todo_id', $todo['id'])
            ->findColumn('email') ?? [];

        return [
            'id' => (int) $todo['id'],
            'categoryId' => (int) $todo['domain_id'],
            'categoryName' => $todo['category_name'] ?? null,
            'title' => $todo['title'],
            'description' => $todo['description'],
            'contactEmail' => implode(', ', $emails),
            'contactEmails' => $emails,
            'endDate' => $todo['end_date'],
            'priority' => $todo['priority'],
            'status' => $todo['status'],
            'createdAt' => $todo['created_at'] ?? null,
            'updatedAt' => $todo['updated_at'] ?? null,
        ];
    }

    private function todoData(array $payload, bool $creating = false): array
    {
        $data = [];

        if (isset($payload['category_id']) || isset($payload['categoryId']) || isset($payload['domain_id']) || isset($payload['domainId'])) {
            $data['domain_id'] = (int) ($payload['category_id'] ?? $payload['categoryId'] ?? $payload['domain_id'] ?? $payload['domainId']);
        }

        if (array_key_exists('title', $payload)) {
            $data['title'] = trim((string) $payload['title']);
        }

        if (array_key_exists('description', $payload)) {
            $data['description'] = trim((string) $payload['description']);
        }

        if (array_key_exists('end_date', $payload) || array_key_exists('endDate', $payload)) {
            $data['end_date'] = (string) ($payload['end_date'] ?? $payload['endDate']);
        }

        if ($creating || array_key_exists('priority', $payload)) {
            $data['priority'] = (string) ($payload['priority'] ?? 'Mittel');
        }

        if ($creating || array_key_exists('status', $payload)) {
            $data['status'] = (string) ($payload['status'] ?? 'Offen');
        }

        return $data;
    }

    private function validateTodoPayload(array $payload, bool $creating): array
    {
        $errors = [];
        $categoryId = $payload['category_id'] ?? $payload['categoryId'] ?? $payload['domain_id'] ?? $payload['domainId'] ?? null;
        $title = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $endDate = (string) ($payload['end_date'] ?? $payload['endDate'] ?? '');
        $priority = (string) ($payload['priority'] ?? 'Mittel');
        $status = (string) ($payload['status'] ?? 'Offen');
        $emails = $this->normalizeEmails($payload['contact_emails'] ?? $payload['contactEmails'] ?? $payload['contactEmail'] ?? []);

        if (! $creating && ! $this->hasAnyKnownField($payload, [
            'category_id',
            'categoryId',
            'domain_id',
            'domainId',
            'title',
            'description',
            'end_date',
            'endDate',
            'priority',
            'status',
            'contact_emails',
            'contactEmails',
            'contactEmail',
        ])) {
            $errors['payload'] = 'Mindestens ein Feld muss gesendet werden.';
        }

        if (($creating || $categoryId !== null) && $this->validateId($categoryId) === null) {
            $errors['category_id'] = 'Kategorie-ID ist erforderlich und muss eine positive Zahl sein.';
        } elseif ($categoryId !== null && $this->categories->find((int) $categoryId) === null) {
            $errors['category_id'] = 'Kategorie existiert nicht.';
        }

        if (($creating || array_key_exists('title', $payload)) && $title === '') {
            $errors['title'] = 'Titel ist erforderlich.';
        } elseif (mb_strlen($title) > 150) {
            $errors['title'] = 'Titel darf maximal 150 Zeichen lang sein.';
        }

        if (($creating || array_key_exists('description', $payload)) && $description === '') {
            $errors['description'] = 'Beschreibung ist erforderlich.';
        }

        if (($creating || array_key_exists('end_date', $payload) || array_key_exists('endDate', $payload)) && $endDate === '') {
            $errors['end_date'] = 'Enddatum ist erforderlich.';
        } elseif (($creating || array_key_exists('end_date', $payload) || array_key_exists('endDate', $payload)) && ! $this->isValidDate($endDate)) {
            $errors['end_date'] = 'Enddatum muss im Format YYYY-MM-DD gesendet werden.';
        }

        if (($creating || array_key_exists('priority', $payload)) && ! in_array($priority, ['Hoch', 'Mittel', 'Niedrig'], true)) {
            $errors['priority'] = 'Prioritaet muss Hoch, Mittel oder Niedrig sein.';
        }

        if (($creating || array_key_exists('status', $payload)) && ! in_array($status, ['Offen', 'In Arbeit', 'Erledigt'], true)) {
            $errors['status'] = 'Status muss Offen, In Arbeit oder Erledigt sein.';
        }

        foreach ($emails as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['contact_emails'] = 'Alle Kontakt-E-Mails muessen gueltig sein.';
                break;
            }
        }

        return $errors;
    }

    private function isValidDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
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

    private function replaceContacts(int $todoId, array|string $emails): void
    {
        $this->contacts->where('todo_id', $todoId)->delete();

        $rows = array_map(
            fn (string $email): array => [
                'todo_id' => $todoId,
                'email' => $email,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            $this->normalizeEmails($emails),
        );

        if ($rows !== []) {
            $this->contacts->insertBatch($rows);
        }
    }

    private function normalizeEmails(array|string $emails): array
    {
        if (is_string($emails)) {
            $emails = preg_split('/[;,]/', $emails) ?: [];
        }

        return array_values(array_filter(array_map(
            fn ($email): string => trim((string) $email),
            $emails,
        )));
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

    private function fail(string $message, int $status = 400, array $errors = []): ResponseInterface
    {
        return $this->respond([
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
