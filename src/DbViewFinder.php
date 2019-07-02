<?php

namespace Kiroushi\DbBlade;

use App\PartialViewModel;
use App\Utility\CurrentSite;
use App\Utility\CurrentCity;
use Illuminate\View\FileViewFinder;

class DbViewFinder extends FileViewFinder
{
    protected $modelName = null;
    protected $nameField = null;

    public function __construct(string $modelName, string $nameField)
    {
        $this->modelName = $modelName;
        $this->nameField = $nameField;
    }

    public function model(string $modelName, string $nameField = null)
    {
        $this->modelName = $modelName;

        if ($nameField !== null) {
            $this->nameField = $nameField;
        }
    }

    public function field(string $nameField)
    {
        $this->nameField = $nameField;
    }

    /**
     * Get the fully qualified location of the view.
     *
     * @param  string  $name
     * @return string
     */
    public function find($name)
    {
        $site = app(CurrentSite::class);
        $city = app(CurrentCity::class);

        return ($this->modelName)::where($this->nameField, "{$site->id}.{$city->trans}.{$name}")->first() ??
            ($this->modelName)::where($this->nameField, "{$site->id}.{$name}")->first() ??
            PartialViewModel::where($this->nameField, "{$site->id}.{$name}")->firstOrFail();
    }
}
