<?php
namespace Czim\CmsModels\Test\Analyzer\Processor\Steps;

use Czim\CmsModels\Support\Data\ModelInformation;
use Czim\CmsModels\Test\Helpers\Analyzer\UsesAbstractTraitAnalyzerStep;
use Czim\CmsModels\Test\Helpers\Models\Analyzer\TestOrderable;

class AbstractTraitAnalyzerTest extends AbstractStepCase
{

    /**
     * @test
     */
    function it_returns_traits_used_by_a_model()
    {
        // Setup
        $model    = new TestOrderable;
        $analyzer = $this->prepareAnalyzerSetup($model);

        // Test
        $step = new UsesAbstractTraitAnalyzerStep;
        $step->setAnalyzer($analyzer);

        static::assertEquals(['Czim\Listify\Listify'], array_values($step->publicGetTraitNames()));
    }

    /**
     * @test
     */
    function it_returns_that_a_trait_is_used_by_a_model()
    {
        // Setup
        $model    = new TestOrderable;
        $analyzer = $this->prepareAnalyzerSetup($model);

        // Test
        $step = new UsesAbstractTraitAnalyzerStep;
        $step->setAnalyzer($analyzer);

        static::assertTrue($step->publicModelHasTrait('Czim\Listify\Listify'));
    }

    /**
     * @test
     */
    function it_returns_that_a_list_of_traits_is_used_by_a_model()
    {
        // Setup
        $model    = new TestOrderable;
        $analyzer = $this->prepareAnalyzerSetup($model);

        // Test
        $step = new UsesAbstractTraitAnalyzerStep;
        $step->setAnalyzer($analyzer);

        static::assertTrue($step->publicModelHasTrait(['Czim\Listify\Listify']));
    }

}
