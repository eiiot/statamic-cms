<?php

namespace Statamic\Fieldtypes;

use Illuminate\Support\Arr;
use Statamic\CP\Column;
use Statamic\Fields\Fieldtype;

abstract class Relationship extends Fieldtype
{
    protected static $preloadable = true;
    protected $component = 'relationship';
    protected $indexComponent = 'relationship';
    protected $itemComponent = 'related-item';
    protected $formComponent;
    protected $categories = ['relationship'];
    protected $canEdit = false;
    protected $canCreate = false;
    protected $canSearch = false;
    protected $statusIcons = false;
    protected $taggable = false;
    protected $defaultValue = [];
    protected $formComponentProps = [
        '_' => '_' // forces an object in js
    ];
    protected $extraConfigFields = [];
    protected $configFields = [
        'max_items' => [
            'type' => 'integer',
            'instructions' => 'Set a maximum number of selectable items',
        ],
        'mode' => [
            'type' => 'radio',
            'options' => [
                'default' => 'Default (Drag and drop UI with item selector in a stack)',
                'select' => 'Select (A dropdown field with prepopulated options)',
                'typeahead' => 'Typeahead (A dropdown field with options requested as you type)'
            ]
        ]
    ];

    protected function configFieldItems(): array
    {
        return array_merge($this->configFields, $this->extraConfigFields);
    }

    public function preProcess($data)
    {
        return Arr::wrap($data);
    }

    public function preProcessConfig($data)
    {
        $data = $this->preProcess($data);

        return $this->config('max_items') === 1 ? Arr::first($data) : $data;
    }

    public function preProcessIndex($data)
    {
        return $this->augment($data)->map(function ($item) use ($data) {
            return [
                'id' => method_exists($item, 'id') ? $item->id() : $item->handle(),
                'title' => method_exists($item, 'title') ? $item->title() : $item->get('title'),
                'edit_url' => $item->editUrl(),
                'published' => $this->statusIcons ? $item->published() : null,
            ];
        });
    }

    public function process($data)
    {
        if ($data === null || $data === []) {
            return null;
        }

        if ($this->field->get('max_items') === 1) {
            return $data[0];
        }

        return $data;
    }

    public function rules(): array
    {
        $rules = ['array'];

        if ($max = $this->config('max_items')) {
            $rules[] = 'max:' . $max;
        }

        return $rules;
    }

    public function preload()
    {
        return [
            'data' => $this->getItemData($this->field->value())->all(),
            'columns' => $this->getColumns(),
            'itemDataUrl' => $this->getItemDataUrl(),
            'baseSelectionsUrl' => $this->getBaseSelectionsUrl(),
            'getBaseSelectionsUrlParameters' => $this->getBaseSelectionsUrlParameters(),
            'itemComponent' => $this->getItemComponent(),
            'canEdit' => $this->canEdit(),
            'canCreate' => $this->canCreate(),
            'canSearch' => $this->canSearch(),
            'statusIcons' => $this->statusIcons,
            'creatables' => $this->getCreatables(),
            'formComponent' => $this->getFormComponent(),
            'formComponentProps' => $this->getFormComponentProps(),
            'taggable' => $this->getTaggable(),
        ];
    }

    protected function canCreate()
    {
        if ($this->canCreate === false) {
            return false;
        }

        return $this->config('create', true);
    }

    protected function canEdit()
    {
        if ($this->canEdit === false) {
            return false;
        }

        return $this->config('edit', true);
    }

    protected function canSearch()
    {
        return $this->canSearch;
    }

    protected function getItemComponent()
    {
        return $this->itemComponent;
    }

    protected function getFormComponent()
    {
        return $this->formComponent;
    }

    protected function getFormComponentProps()
    {
        return $this->formComponentProps;
    }

    protected function getColumns()
    {
        return [
            Column::make('title'),
        ];
    }

    protected function getItemDataUrl()
    {
        return cp_route('relationship.data');
    }

    protected function getBaseSelectionsUrl()
    {
        return cp_route('relationship.index');
    }

    protected function getBaseSelectionsUrlParameters()
    {
        return [];
    }

    protected function getCreatables()
    {
        return [];
    }

    protected function getCreateItemUrl()
    {
        //
    }

    public function getItemData($values)
    {
        return collect($values)->map(function ($id) {
            return $this->toItemArray($id);
        })->values();
    }

    abstract protected function toItemArray($id);

    protected function invalidItemArray($id)
    {
        return [
            'id' => $id,
            'title' => $id,
            'invalid' => true
        ];
    }

    public function augment($values)
    {
        return collect($values)->map(function ($value) {
            return $this->augmentValue($value);
        });
    }

    protected function augmentValue($value)
    {
        return $value;
    }

    abstract public function getIndexItems($request);

    public function getSortColumn($request)
    {
        return $request->get('sort');
    }

    public function getSortDirection($request)
    {
        return $request->get('order', 'asc');
    }

    protected function getTaggable()
    {
        return $this->taggable;
    }
}
