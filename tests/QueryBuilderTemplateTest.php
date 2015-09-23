<?php
/**
 * Tests all exposed index and show endpoints
 */
use DummyScopes\UserAgeBetweenScope;
use DummyScopes\UserGenderScope;
use DummyScopes\UserGroupScope;
use DummyScopes\UserPostCountBetweenScope;
use DummyScopes\PostIsMaxDaysOldScope;
use DummyScopes\InvalidScopeTest;

use DummyTemplates\PostTemplateFactory;
use Illuminate\FileSystem\Filesystem;
use Illuminate\FileSystem\ClassFinder;

use Chalcedonyt\QueryBuilderTemplate\Scopes\ScopeFactory;

class QueryBuilderTemplateTest extends Orchestra\Testbench\TestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->app['config']->set('database.default','sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');

       $this->migrate();
    }

    /**
     * run package database migrations
     *
     * @return void
     */
    public function migrate()
    {
        $fileSystem = new Filesystem;
        $classFinder = new ClassFinder;

        foreach($fileSystem->files(__DIR__ . "/migrations") as $file)
        {
            $fileSystem->requireOnce($file);
            $migrationClass = $classFinder->findClass($file);

            (new $migrationClass)->up();
        }
        foreach($fileSystem->files(__DIR__ . "/seeds") as $file)
        {
            $fileSystem->requireOnce($file);
            $migrationClass = $classFinder->findClass($file);

            (new $migrationClass)->run();
        }
    }

    public function testRequired()
    {
        $factory = new PostTemplateFactory();
        $template = $factory -> create();

        //all
        $this -> assertEquals( $this -> numberOfResults( $template ), 40 );

        //10 young users*2
        $young_scope = new UserAgeBetweenScope(20, 30);
        $template -> addRequired($young_scope);
        $this -> assertEquals( $this -> numberOfResults( $template ), 20 );

        //5 male young users*2
        $male_scope = new UserGenderScope(1);
        $template -> addRequired($male_scope);
        $this -> assertEquals( $this -> numberOfResults( $template ), 10 );

        $post_age_scope = new PostIsMaxDaysOldScope(7);
        $template -> addRequired($post_age_scope);
        $this -> assertEquals( $this -> numberOfResults( $template ), 5 );

    }

    //when a scope is added with a Join request that the template cannot handle
    public function testJoinInvalidScope()
    {
        $factory = new PostTemplateFactory();
        $invalid_scope = new InvalidScopeTest();
        $template = $factory -> create();
        $template -> addRequired($invalid_scope);

        $this -> setExpectedException('Chalcedonyt\QueryBuilderTemplate\Exception\QueryBuilderTemplateException');
        $template -> generate();
    }

    //test the creation of scope from an array input ( and vice versa )
    public function testScopeFactory()
    {
        // test case 1: test the translation of array input into query builder scope
        $template_factory = new PostTemplateFactory();
        $original_scope = new PostIsMaxDaysOldScope(5);
        $template = $template_factory -> create();
        $template -> addRequired( $original_scope );

        $provided_input = ['daysOld' => 5];
        $expected_result = $this -> numberOfResults( $template );

        $this -> translateArrayToScope( $provided_input, $expected_result );

        // test case 2: test the translation of query builder scope into array output        
        $provided_input = $original_scope;
        $expected_result = ['daysOld' => 5];

        $this -> translateScopeToArray($provided_input, $expected_result);
    }

    private function translateArrayToScope( $provided_input, $expected_result )
    {
        $scope_factory = new ScopeFactory(PostIsMaxDaysOldScope::class, $provided_input);
        $scope = $scope_factory -> create();

        $template_factory = new PostTemplateFactory();
        $template = $template_factory -> create();
        $template -> addRequired( $scope );

        $this -> assertEquals( $this -> numberOfResults( $template ), $expected_result );
    }

    private function translateScopeToArray( $provided_input, $expected_result )
    {
        $this -> assertEquals( $provided_input -> toArray(), $expected_result );
    }

    private function numberOfResults( $template )
    {
        return count($template -> generate() -> get() );
    }

}

?>