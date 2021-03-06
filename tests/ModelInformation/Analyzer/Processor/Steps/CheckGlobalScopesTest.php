<?php
namespace Czim\CmsModels\Test\ModelInformation\Analyzer\Processor\Steps;

use Czim\CmsModels\ModelInformation\Analyzer\Processor\Steps\CheckGlobalScopes;
use Czim\CmsModels\ModelInformation\Data\ModelInformation;
use Czim\CmsModels\Test\Helpers\Models\Analyzer\TestActivatable;
use Czim\CmsModels\Test\Helpers\Models\Analyzer\TestGlobalScope;

/**
 * Class CheckGlobalScopesTest
 *
 * @group analysis
 */
class CheckGlobalScopesTest extends AbstractStepCase
{

    /**
     * @test
     */
    function it_detects_when_a_model_has_global_scopes()
    {
        // Setup
        $model    = new TestGlobalScope;
        $analyzer = $this->prepareAnalyzerSetup($model);
        $info     = new ModelInformation;

        // Test
        $step = new CheckGlobalScopes;
        $step->setAnalyzer($analyzer);

        $info = $step->analyze($info);

        static::assertTrue($info['meta']['disable_global_scopes']);
    }

    /**
     * @test
     */
    function it_does_not_detect_a_model_without_global_scopes_as_having_them()
    {
        // Setup
        $model    = new TestActivatable;
        $analyzer = $this->prepareAnalyzerSetup($model);
        $info     = new ModelInformation;

        // Test
        $step = new CheckGlobalScopes;
        $step->setAnalyzer($analyzer);

        $info = $step->analyze($info);

        static::assertNotTrue($info['meta']['disable_global_scopes']);
    }

}
