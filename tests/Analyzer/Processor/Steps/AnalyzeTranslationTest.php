<?php
namespace Czim\CmsModels\Test\Analyzer\Processor\Steps;

use Czim\CmsModels\Analyzer\Processor\Steps\AnalyzeTranslation;
use Czim\CmsModels\Analyzer\TranslationAnalyzer;
use Czim\CmsModels\Support\Data\ModelAttributeData;
use Czim\CmsModels\Support\Data\ModelInformation;
use Czim\CmsModels\Test\Helpers\Models\Analyzer\TestActivatable;
use Czim\CmsModels\Test\Helpers\Models\Analyzer\TestTranslatedModel;
use Dimsav\Translatable\Translatable;
use Mockery;

/**
 * Class AnalyzeTranslationTest
 *
 * @group analysis
 */
class AnalyzeTranslationTest extends AbstractStepCase
{

    /**
     * @test
     */
    function it_does_not_analyze_an_untranslated_model()
    {
        $this->app['config']->set('cms-models.analyzer.traits.translatable', [
            Translatable::class,
        ]);

        // Setup
        $model    = new TestActivatable;
        $analyzer = $this->prepareAnalyzerSetup($model);
        $info     = new ModelInformation;

        // Test
        $step = new AnalyzeTranslation;
        $step->setAnalyzer($analyzer);

        $info = $step->analyze($info);

        static::assertNotTrue($info['translated']);
    }

    /**
     * @test
     */
    function it_analyzes_translation_of_a_model()
    {
        $this->app['config']->set('cms-models.analyzer.traits.translatable', [
            Translatable::class,
        ]);

        $translationInfo = new ModelInformation([
            'attributes' => [
                'title' => new ModelAttributeData([
                    'name'       => 'title',
                    'translated' => true,
                ]),
                'description' => new ModelAttributeData([
                    'name'       => 'description',
                    'translated' => true,
                ]),
                // Only attributes marked 'translated' should be merged
                'ignored' => new ModelAttributeData([
                    'name'       => 'ignored',
                    'translated' => false,
                ]),
            ],
        ]);

        /** @var TranslationAnalyzer|Mockery\Mock $transAnalyzerMock */
        $transAnalyzerMock = Mockery::mock(TranslationAnalyzer::class);

        $transAnalyzerMock->shouldReceive('setModelAnalyzer')->once();
        $transAnalyzerMock->shouldReceive('analyze')->once()->andReturn($translationInfo);

        $this->app->instance(TranslationAnalyzer::class, $transAnalyzerMock);

        // Setup
        $model    = new TestTranslatedModel;
        $analyzer = $this->prepareAnalyzerSetup($model);
        $info     = new ModelInformation;

        // Test
        $step = new AnalyzeTranslation;
        $step->setAnalyzer($analyzer);

        $info = $step->analyze($info);

        static::assertTrue($info['translated']);
        static::assertEquals('translatable', $info['translation_strategy']);
        static::assertInternalType('array', $info['attributes']);
        static::assertEquals(['title', 'description'], array_keys($info['attributes']));
    }


}
