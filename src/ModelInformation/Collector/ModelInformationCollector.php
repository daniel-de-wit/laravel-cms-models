<?php
namespace Czim\CmsModels\ModelInformation\Collector;

use Czim\CmsCore\Contracts\Core\CoreInterface;
use Czim\CmsCore\Support\Enums\Component;
use Czim\CmsModels\Contracts\ModelInformation\Analyzer\ModelAnalyzerInterface;
use Czim\CmsModels\Contracts\ModelInformation\Data\ModelInformationInterface;
use Czim\CmsModels\Contracts\ModelInformation\ModelInformationCollectorInterface;
use Czim\CmsModels\Contracts\ModelInformation\ModelInformationEnricherInterface;
use Czim\CmsModels\Contracts\ModelInformation\Collector\ModelInformationFileReaderInterface;
use Czim\CmsModels\Contracts\ModelInformation\ModelInformationInterpreterInterface;
use Czim\CmsModels\Contracts\Support\ModuleHelperInterface;
use Czim\CmsModels\Exceptions\ModelConfigurationDataException;
use Czim\CmsModels\Exceptions\ModelInformationCollectionException;
use Czim\CmsModels\ModelInformation\Data\ModelInformation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\SplFileInfo;
use UnexpectedValueException;

class ModelInformationCollector implements ModelInformationCollectorInterface
{

    /**
     * @var ModuleHelperInterface
     */
    protected $moduleHelper;

    /**
     * @var ModelAnalyzerInterface
     */
    protected $modelAnalyzer;

    /**
     * @var ModelInformationFileReaderInterface
     */
    protected $informationReader;

    /**
     * @var ModelInformationEnricherInterface
     */
    protected $informationEnricher;

    /**
     * @var ModelInformationInterpreterInterface
     */
    protected $informationInterpreter;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var Collection|ModelInformationInterface[]|ModelInformation[]
     */
    protected $information;

    /**
     * List of model classes included for the CMS.
     *
     * @var string[]
     */
    protected $modelClasses = [];

    /**
     * List of CMS model information files.
     *
     * @var SplFileInfo[]
     */
    protected $cmsModelFiles = [];


    /**
     * @param ModuleHelperInterface                $moduleHelper
     * @param ModelAnalyzerInterface               $modelAnalyzer
     * @param ModelInformationFileReaderInterface  $informationReader
     * @param ModelInformationEnricherInterface    $informationEnricher
     * @param ModelInformationInterpreterInterface $informationInterpreter
     * @param Filesystem                           $files
     */
    public function __construct(
        ModuleHelperInterface $moduleHelper,
        ModelAnalyzerInterface $modelAnalyzer,
        ModelInformationFileReaderInterface $informationReader,
        ModelInformationEnricherInterface $informationEnricher,
        ModelInformationInterpreterInterface $informationInterpreter,
        Filesystem $files
    ) {
        $this->moduleHelper           = $moduleHelper;
        $this->modelAnalyzer          = $modelAnalyzer;
        $this->informationReader      = $informationReader;
        $this->informationEnricher    = $informationEnricher;
        $this->informationInterpreter = $informationInterpreter;
        $this->files                  = $files;
    }


    /**
     * Collects and returns information about models.
     *
     * @return Collection|ModelInformationInterface[]
     */
    public function collect()
    {
        $this->information = new Collection;

        $this->cmsModelFiles = $this->getCmsModelFiles();
        $this->modelClasses  = $this->getModelsToCollect();

        $this->collectRawModels()
             ->collectCmsModels()
             ->enrichModelInformation();

        return $this->information;
    }

    /**
     * Collects information about config-defined app model classes.
     *
     * @return $this
     * @throws ModelInformationCollectionException
     */
    protected function collectRawModels()
    {
        foreach ($this->modelClasses as $class) {

            $key = $this->moduleHelper->modelInformationKeyForModel($class);

            try {
                $this->information->put($key, $this->modelAnalyzer->analyze($class));

            } catch (\Exception $e) {

                // Wrap and decorate exceptions so it is easier to track the problem source
                throw (new ModelInformationCollectionException(
                    "Issue analyzing model {$class}: \n{$e->getMessage()}",
                    (int) $e->getCode(),
                    $e
                ))
                    ->setModelClass($class);
            }
        }

        return $this;
    }

    /**
     * Collects information from dedicated CMS model information classes.
     *
     * @return $this
     * @throws ModelInformationCollectionException
     */
    protected function collectCmsModels()
    {
        foreach ($this->cmsModelFiles as $file) {

            try {
                $this->collectSingleCmsModelFromFile($file);

            } catch (\Exception $e) {

                $message = $e->getMessage();

                if ($e instanceof ModelConfigurationDataException) {
                    $message = "{$e->getMessage()} ({$e->getDotKey()})";
                }

                // Wrap and decorate exceptions so it is easier to track the problem source
                throw (new ModelInformationCollectionException(
                    "Issue reading/interpreting model configuration file {$file->getRealPath()}: \n{$message}",
                    (int) $e->getCode(),
                    $e
                ))
                    ->setConfigurationFile($file->getRealPath());
            }
        }

        return $this;
    }

    /**
     * Collects CMS configuration information from a given Spl file.
     *
     * @param SplFileInfo $file
     */
    protected function collectSingleCmsModelFromFile(SplFileInfo $file)
    {
        $info = $this->informationReader->read($file->getRealPath());

        if ( ! is_array($info)) {
            $path = basename($file->getRelativePath() ?: $file->getRealPath());
            throw new UnexpectedValueException(
                "Incorrect data from CMS model information file: '{$path}'"
            );
        }

        $info = $this->informationInterpreter->interpret($info);

        $modelClass = $this->makeModelFqnFromCmsModelPath(
            $file->getRelativePathname()
        );

        $key = $this->moduleHelper->modelInformationKeyForModel($modelClass);

        if ( ! $this->information->has($key)) {
            $this->getCore()->log('debug', "CMS model data for unset model information key '{$key}'");
            return;
        }

        /** @var ModelInformationInterface $originalInfo */
        $originalInfo = $this->information->get($key);
        $originalInfo->merge($info);

        $this->information->put($key, $originalInfo);
    }

    /**
     * Enriches collected model information, extrapolating from available data.
     *
     * @return $this
     */
    protected function enrichModelInformation()
    {
        $this->information = $this->informationEnricher->enrichMany($this->information);

        return $this;
    }

    /**
     * Returns a list of model FQNs for which to collect information.
     *
     * @return string[]
     */
    protected function getModelsToCollect()
    {
        return config('cms-models.models', []);
    }

    /**
     * @return SplFileInfo[]
     */
    protected function getCmsModelFiles()
    {
        $cmsModelsDir = config('cms-models.collector.source.dir');

        if ( ! $cmsModelsDir || ! $this->files->isDirectory($cmsModelsDir)) {
            return [];
        }

        return $this->files->allFiles($cmsModelsDir);
    }

    /**
     * Returns the FQN for the model that is related to a given cms model
     * information file path.
     *
     * @param string $path  relative path
     * @return string
     */
    protected function makeModelFqnFromCmsModelPath($path)
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension) {
            $path = substr($path, 0, -1 * strlen($extension) - 1);
        }

        return rtrim(config('cms-models.collector.source.models-namespace'), '\\')
             . '\\' . str_replace('/', '\\', $path);
    }

    /**
     * @return CoreInterface
     */
    protected function getCore()
    {
        return app(Component::CORE);
    }

}
