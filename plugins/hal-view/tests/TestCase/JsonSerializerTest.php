<?php

namespace MixerApi\HalView\Test\TestCase;

use Cake\Datasource\FactoryLocator;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\PaginatorHelper;
use MixerApi\HalView\JsonSerializer;
use MixerApi\HalView\View\HalJsonView;

class JsonSerializerTest extends TestCase
{
    /**
     * @var string
     */
    private const EXT = 'haljson';

    /**
     * @var string
     */
    private const VIEW_CLASS = 'MixerApi/HalView.HalJson';

    /**
     * @var string[]
     */
    public array $fixtures = [
        'plugin.MixerApi/HalView.Actors',
        'plugin.MixerApi/HalView.FilmActors',
        'plugin.MixerApi/HalView.Films',
    ];

    /**
     * @var ServerRequest
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        static::setAppNamespace('MixerApi\HalView\Test\App');
        $request = (new ServerRequest([
            'url' => '/',
            'params' => [
                'plugin' => null,
                'controller' => 'Actor',
                'action' => 'index',
            ],
        ]))->withEnv('HTTP_ACCEPT', 'application/hal+json');

        $this->request = $request->withAttribute('paging', [
            'Actor' => [
                'page' => 1,
                'current' => 1,
                'count' => 60,
                'prevPage' => false,
                'nextPage' => true,
                'pageCount' => 1,
                'sort' => null,
                'direction' => null,
                'limit' => null,
                'start' => 1,
                'end' => 3,
            ],
        ]);
        Router::reload();
        Router::createRouteBuilder('/')->scope('/', function (RouteBuilder $builder) {
            $builder->setExtensions(['json']);
            $builder->connect('/', ['controller' => 'Actors', 'action' => 'index']);
            $builder->connect('/{controller}/{action}/*');
            $builder->connect('/{plugin}/{controller}/{action}/*');
        });
        Router::setRequest($this->request);
        $this->response = new Response();
    }

    public function test_collection(): void
    {
        $actor = FactoryLocator::get('Table')->get('Actors');
        $result = $actor->find()->contain('Films')->limit(1)->all();

        $paginator = new PaginatorHelper(
            new HalJsonView($this->request, $this->response),
            ['templates' => 'MixerApi/HalView.paginator-template']
        );
        $paginator->defaultModel('Actor');

        $jsonSerializer = new JsonSerializer($result, $this->request, $paginator);

        $json = $jsonSerializer->asJson(JSON_PRETTY_PRINT);

        $this->assertIsString($json);
        $this->assertIsObject(json_decode($json));
    }

    public function test_item(): void
    {
        /** @var Table $actorsTable */
        $actorsTable = FactoryLocator::get('Table')->get('Actors');
        $result = $actorsTable->get(
            primaryKey: 1,
            args: [
                'contain' => 'Films'
            ]
        );

        $paginator = new PaginatorHelper(
            new HalJsonView($this->request, $this->response),
            ['templates' => 'MixerApi/HalView.paginator-template']
        );
        $paginator->defaultModel('Actor');

        $jsonSerializer = new JsonSerializer($result, $this->request, $paginator);

        $json = $jsonSerializer->asJson(JSON_PRETTY_PRINT);

        $this->assertIsString($json);
        $this->assertIsObject(json_decode($json));
    }

    public function test_get_data(): void
    {
        $actor = FactoryLocator::get('Table')->get('Actors');
        $result = $actor->find()->contain('Films')->limit(1)->all();

        $paginator = new PaginatorHelper(
            new HalJsonView($this->request, $this->response),
            ['templates' => 'MixerApi/HalView.paginator-template']
        );
        $paginator->defaultModel('Actor');

        $jsonSerializer = new JsonSerializer($result, $this->request, $paginator);

        $this->assertIsArray($jsonSerializer->getData());
    }

    public function test_as_json_throws_run_time_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        (new JsonSerializer(NAN))->asJson(0);
    }
}
