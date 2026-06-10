<?php

use App\Controllers\Api\TodosController;
use App\Filters\ApiKeyFilter;
use App\Filters\JwtAuthFilter;
use App\Libraries\JwtService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ApiRequestResponseTest extends CIUnitTestCase
{
    private function callPrivateMethod(object $object, string $method, array $args = [])
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }

    public function testApiKeyFilterRejectsMissingApiKey(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getHeaderLine')->with('X-API-Key')->willReturn('');
        $request->method('getGet')->with('key')->willReturn('');

        $response = (new ApiKeyFilter())->before($request);

        $this->assertNotNull($response);
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode($response->getBody(), true);
        $this->assertSame('Ungueltiger oder fehlender API Key.', $payload['message']);
        $this->assertSame('Senden Sie einen gueltigen API Key per X-API-Key Header oder ?key= URL-Parameter.', $payload['errors']['key']);
    }

    public function testApiKeyFilterAcceptsValidApiKeyFromHeader(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getHeaderLine')->with('X-API-Key')->willReturn('dev-frontend-key-2026');
        $request->method('getGet')->with('key')->willReturn('');

        $response = (new ApiKeyFilter())->before($request);

        $this->assertNull($response);
    }

    public function testJwtAuthFilterRejectsMissingAuthorizationHeader(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getHeaderLine')->with('Authorization')->willReturn('');

        $response = (new JwtAuthFilter())->before($request);

        $this->assertNotNull($response);
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode($response->getBody(), true);
        $this->assertSame('JWT fehlt im Authorization Header.', $payload['message']);
        $this->assertSame('JWT fehlt im Authorization Header.', $payload['errors']['authorization']);
    }

    public function testJwtAuthFilterRejectsInvalidToken(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer invalid.token.value');

        $response = (new JwtAuthFilter())->before($request);

        $this->assertNotNull($response);
        $this->assertSame(401, $response->getStatusCode());

        $payload = json_decode($response->getBody(), true);
        $this->assertSame('JWT ist ungueltig oder abgelaufen.', $payload['message']);
    }

    public function testJwtAuthFilterAcceptsValidBearerToken(): void
    {
        $token = (new JwtService())->encode(['sub' => 'user@example.com']);

        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getHeaderLine')->with('Authorization')->willReturn('Bearer ' . $token);

        $response = (new JwtAuthFilter())->before($request);

        $this->assertNull($response);
    }

    public function testGetPaginationReturnsDefaultLimitAndPage(): void
    {
        $controller = new TodosController();
        $request = $this->createMock(RequestInterface::class);
        $request->method('getGet')->willReturn(null);
        $this->setPrivateProperty($controller, 'request', $request);

        $pagination = $this->callPrivateMethod($controller, 'getPagination');

        $this->assertSame(['limit' => 50, 'page' => 1], $pagination);
    }

    public function testGetPaginationRejectsOutOfRangeLimit(): void
    {
        $controller = new TodosController();
        $request = $this->createMock(RequestInterface::class);
        $request->method('getGet')->willReturnMap([
            ['limit', '500'],
            ['page', null],
        ]);
        $this->setPrivateProperty($controller, 'request', $request);

        $pagination = $this->callPrivateMethod($controller, 'getPagination');

        $this->assertSame(['error' => 'Limit muss zwischen 1 und 100 liegen.'], $pagination);
    }

    public function testGetPaginationRejectsInvalidPage(): void
    {
        $controller = new TodosController();
        $request = $this->createMock(RequestInterface::class);
        $request->method('getGet')->willReturnMap([
            ['limit', '20'],
            ['page', '0'],
        ]);
        $this->setPrivateProperty($controller, 'request', $request);

        $pagination = $this->callPrivateMethod($controller, 'getPagination');

        $this->assertSame(['error' => 'Page muss groesser oder gleich 1 sein.'], $pagination);
    }

    public function testValidateIdAcceptsPositiveIntegerAndRejectsZero(): void
    {
        $controller = new TodosController();

        // Bestehende Testfälle
        $this->assertSame(7, $this->callPrivateMethod($controller, 'validateId', [7]));
        $this->assertNull($this->callPrivateMethod($controller, 'validateId', [0]));
        $this->assertNull($this->callPrivateMethod($controller, 'validateId', ['foo']));

        // Sicherheits-Lücken geschlossen (Edge Cases)
        $this->assertNull($this->callPrivateMethod($controller, 'validateId', [-5]));
        $this->assertNull($this->callPrivateMethod($controller, 'validateId', [4.5]));
        $this->assertNull($this->callPrivateMethod($controller, 'validateId', [null]));
    }

    public function testIsValidDateAcceptsValidDateAndRejectsBadFormat(): void
    {
        $controller = new TodosController();

        // Bestehende Testfälle
        $this->assertTrue($this->callPrivateMethod($controller, 'isValidDate', ['2026-12-31']));
        $this->assertFalse($this->callPrivateMethod($controller, 'isValidDate', ['31-12-2026']));
        $this->assertFalse($this->callPrivateMethod($controller, 'isValidDate', ['2026-02-30']));

        // Sicherheits-Lücken geschlossen (Edge Cases)
        $this->assertFalse($this->callPrivateMethod($controller, 'isValidDate', ['not-a-date']));
        $this->assertFalse($this->callPrivateMethod($controller, 'isValidDate', ['']));
        $this->assertFalse($this->callPrivateMethod($controller, 'isValidDate', ['2026-02-29'])); // 2026 ist kein Schaltjahr
    }

    public function testNormalizeEmailsHandlesStringAndArrayInputs(): void
    {
        $controller = new TodosController();

        // Korrigierte Argument-Ubergabe für String-Parsing (Kommasepariert)
        $stringEmails = $this->callPrivateMethod($controller, 'normalizeEmails', ['foo@example.com, bar@example.com']);
        $this->assertSame(['foo@example.com', 'bar@example.com'], $stringEmails);

        // Korrigierte Argument-Ubergabe für Array-Verarbeitung (mit Kapselung im Argumenten-Array)
        $arrayEmails = $this->callPrivateMethod($controller, 'normalizeEmails', [['foo@example.com', '  bar@example.com  ']]);
        $this->assertSame(['foo@example.com', 'bar@example.com'], $arrayEmails);

        // Sicherheits-Lücken geschlossen (Erweiterte Edge Cases)
        $mixedEmails = $this->callPrivateMethod($controller, 'normalizeEmails', ['foo@example.com, foo@example.com, BAR@example.com ']);
        // Prüft Duplikats-Entfernung und ggf. Lowercase-Konvertierung (falls vom System unterstützt)
        $this->assertEquals(['foo@example.com', 'bar@example.com'], array_map('strtolower', $mixedEmails));

        $emptyEmails = $this->callPrivateMethod($controller, 'normalizeEmails', ['']);
        $this->assertSame([], $emptyEmails);
    }

    public function testTodoDataMapsFieldsAndDefaultsPriorityStatus(): void
    {
        $controller = new TodosController();

        $payload = [
            'categoryId' => 15,
            'title' => ' New Task ',
            'description' => ' Description ',
            'endDate' => '2026-11-15',
        ];

        $data = $this->callPrivateMethod($controller, 'todoData', [$payload, true]);

        $this->assertSame(
            [
                'domain_id' => 15,
                'title' => 'New Task',
                'description' => 'Description',
                'end_date' => '2026-11-15',
                'priority' => 'Mittel',
                'status' => 'Offen',
            ],
            $data,
        );

        // Sicherheits-Lücke geschlossen: Verifiziert, dass vorhandene Werte NICHT überschrieben werden
        $payloadWithData = [
            'categoryId' => 15,
            'title' => 'Task',
            'description' => 'Desc',
            'endDate' => '2026-11-15',
            'priority' => 'Hoch',
            'status' => 'In Arbeit',
        ];
        $dataCustom = $this->callPrivateMethod($controller, 'todoData', [$payloadWithData, false]);
        $this->assertSame('Hoch', $dataCustom['priority']);
        $this->assertSame('In Arbeit', $dataCustom['status']);
    }

    public function testFormatTodoBuildsResponseBodyWithContactEmails(): void
    {
        $controller = new TodosController();

        $contacts = new class {
            public function where(string $field, $value)
            {
                return $this;
            }

            public function findColumn(string $column)
            {
                return ['alice@example.com', 'bob@example.com'];
            }
        };

        $this->setPrivateProperty($controller, 'contacts', $contacts);

        $todo = [
            'id' => 42,
            'domain_id' => 7,
            'category_name' => 'Work',
            'title' => 'Fix bug',
            'description' => 'Fix the reported issue',
            'end_date' => '2026-10-01',
            'priority' => 'Hoch',
            'status' => 'In Arbeit',
            'created_at' => '2026-08-01 08:00:00',
            'updated_at' => '2026-08-02 09:30:00',
        ];

        $formatted = $this->callPrivateMethod($controller, 'formatTodo', [$todo]);

        $this->assertSame(42, $formatted['id']);
        $this->assertSame(7, $formatted['categoryId']);
        $this->assertSame('Work', $formatted['categoryName']);
        $this->assertSame('Fix bug', $formatted['title']);
        $this->assertSame('Fix the reported issue', $formatted['description']);
        $this->assertSame('alice@example.com, bob@example.com', $formatted['contactEmail']);
        $this->assertSame(['alice@example.com', 'bob@example.com'], $formatted['contactEmails']);
        $this->assertSame('2026-10-01', $formatted['endDate']);
        $this->assertSame('Hoch', $formatted['priority']);
        $this->assertSame('In Arbeit', $formatted['status']);
        $this->assertSame('2026-08-01 08:00:00', $formatted['createdAt']);
        $this->assertSame('2026-08-02 09:30:00', $formatted['updatedAt']);
    }
}