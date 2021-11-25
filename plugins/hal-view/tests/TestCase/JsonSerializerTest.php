<?php

namespace MixerApi\HalView\Test\TestCase;

use Cake\Datasource\FactoryLocator;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\ResultSet;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\Helper\PaginatorHelper;
use MixerApi\HalView\JsonSerializer;
use MixerApi\Core\Response\ResponseModifier;
use MixerApi\HalView\View\HalJsonView;

class JsonSerializerTest extends TestCase
{
    /**
     * @var string
     */
    private const EXT = 'haljson';

    /**
     * @var string[]
     */
    private const MIME_TYPES = ['application/hal+json','application/vnd.hal+json'];

    /**
     * @var string
     */
    private const VIEW_CLASS = 'MixerApi/HalView.HalJson';

    /**
     * @var string[]
     */
    public $fixtures = [
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
        Router::connect('/', ['controller' => 'Actors', 'action' => 'index']);
        Router::connect('/:controller/:action/*');
        Router::connect('/:plugin/:controller/:action/*');
        Router::setRequest($this->request);
        $this->response = (new ResponseModifier(self::EXT, self::MIME_TYPES, self::VIEW_CLASS))
            ->modify($this->request, new Response());
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
        $actor = FactoryLocator::get('Table')->get('Actors');
        $result = $actor->get(1, [
            'contain' => 'Films'
        ]);

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
}
