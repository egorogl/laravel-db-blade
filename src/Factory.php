<?php

namespace Kiroushi\DbBlade;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\Contracts\Events\Dispatcher as Dispatcher;

class Factory extends ViewFactory
{
    protected $contentField = null;

    /**
     * Create a new dbview factory instance.
     *
     * @param  \Kiroushi\DbBlade\DbViewFinder  $finder
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     */
    public function __construct(DbViewFinder $finder, Dispatcher $events)
    {
        $this->finder = $finder;
        $this->events = $events;

        $this->share('__env', $this);
    }

    /**
     * Set the DB View model and its name field at run time.
     *
     * @param string $modelName
     * @param string|null $nameField
     * @return $this
     */
    public function model(string $modelName, string $nameField = null)
    {
        $this->finder->model($modelName, $nameField);

        return $this;
    }

    /**
     * Set the model name field at run time.
     *
     * @param string $nameField
     * @return $this
     */
    public function field(string $nameField)
    {
        $this->finder->field($nameField);

        return $this;
    }

    /**
     * Set the content field at run time.
     *
     * @param string $contentField
     * @return $this
     */
    public function contentField(string $contentField)
    {
        $this->contentField = $contentField;

        return $this;
    }

    /**
     * Create a new view instance from the given arguments.
     *
     * @param  string  $view
     * @param  Model  $model
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $data
     * @return \Kiroushi\DbBlade\DbView
     */
    protected function viewInstance($view, $model, $data)
    {
        return new DbView($this, $view, $model, $data, $this->contentField);
    }
    
    /**
     * Determine if a given view exists.
     *
     * @param  string  $view
     * @return bool
     */
    public function exists($view)
    {
        try {
            $this->finder->find($view);
        } catch (ModelNotFoundException $e) {
            return false;
        }

        return true;
    }
}
