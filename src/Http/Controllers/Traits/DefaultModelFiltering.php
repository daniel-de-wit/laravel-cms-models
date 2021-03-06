<?php
namespace Czim\CmsModels\Http\Controllers\Traits;

use Czim\CmsCore\Contracts\Core\CoreInterface;
use Czim\CmsModels\Contracts\ModelInformation\Data\ModelInformationInterface;
use Czim\CmsModels\Contracts\Support\Session\ModelListMemoryInterface;
use Czim\CmsModels\Filters\ModelFilter;
use Czim\CmsModels\Filters\ModelFilterData;
use Czim\CmsModels\ModelInformation\Data\ModelInformation;
use Illuminate\Database\Eloquent\Builder;

trait DefaultModelFiltering
{

    /**
     * The current filter settings
     *
     * @var array
     */
    protected $filters = [];

    /**
     * @return array
     */
    protected function getActiveFilters()
    {
        return $this->filters;
    }

    /**
     * Checks and loads filters from the session.
     *
     * @return $this
     */
    protected function checkFilters()
    {
        if ($this->getModelInformation()->list->disable_filters) {
            return $this;
        }

        if ($this->getListMemory()->hasFilters()) {
            $this->retrieveFiltersFromSession();
        }

        return $this;
    }

    /**
     * Applies the current filters, if any, to the model's query builder.
     *
     * @param Builder $query
     * @return $this
     */
    protected function applyFilter($query)
    {
        if ($this->getModelInformation()->list->disable_filters) {
            return $this;
        }

        $filter = $this->makeFilter();

        if ($filter) {
            $filter->apply($query);
        }

        return $this;
    }

    /**
     * Checks and sets the active sort settings.
     *
     * @return $this
     */
    protected function updateFilters()
    {
        $request = request();

        if ($request->filled('_clear')) {
            $this->filters = [];
            $resetPage = ! empty($this->filters);
        } else {
            $this->filters = $request->get('filter', []);
            $resetPage = true;
        }

        $this->storeFiltersInSession();

        if ($resetPage) {
            $this->markResetActivePage();
        }

        return $this;
    }

    /**
     * Stores the currently set filters in the session.
     */
    protected function storeFiltersInSession()
    {
        $this->getListMemory()->setFilters($this->filters);
    }

    /**
     * Retrieves the filters from the session and restores them.
     */
    protected function retrieveFiltersFromSession()
    {
        $this->filters = $this->getListMemory()->getFilters();
    }


    /**
     * Makes and returns filter instance given current context.
     *
     * @return ModelFilter
     */
    protected function makeFilter()
    {
        $data = new ModelFilterData($this->getModelInformation(), $this->filters);

        return new ModelFilter($this->getModelInformation(), $data);
    }

    /**
     * @return CoreInterface
     */
    abstract protected function getCore();

    /**
     * @return string
     */
    abstract protected function getModuleKey();

    /**
     * @return ModelInformationInterface|ModelInformation|null
     */
    abstract protected function getModelInformation();

    /**
     * @param bool $reset
     * @return $this
     */
    abstract protected function markResetActivePage($reset = true);

    /**
     * @return ModelListMemoryInterface
     */
    abstract protected function getListMemory();

}
