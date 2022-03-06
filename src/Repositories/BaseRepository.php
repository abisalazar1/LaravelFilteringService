<?php

namespace Abix\DataFiltering\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;

abstract class BaseRepository
{
    /**
     * Model
     *
     * @var Model
     */
    protected $model;

    /**
     * Sets the model
     */
    public function __construct()
    {
        $this->model = $this->guessModel();
    }

    /**
     * Index
     *
     * @param array $data
     * @param User|null $user
     * @param Builder $query
     * @return Paginate
     */
    public function index(
        array $data,
        ?User $user = null,
        $query = null,
        array $extras = []
    ) {
        $paginateMethod = $this->getPaginationMethod($data['with_pages'] ?? null);

        return $this->model->filter(
            $data,
            $user,
            $query,
            $extras
        )->$paginateMethod($data['per_page'] ?? null);
    }

    /**
     * Gets a single item
     *
     * @param mix $id
     * @return Model
     */
    public function get($id)
    {
        return $this->model->find($id);
    }

    /**
     * Gets run before the model is created
     *
     * @param array $attributes
     * @return void
     */
    protected function beforeCreate(array &$attributes = [])
    {
    }

    /**
     * Creates a record
     *
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes)
    {
        $this->beforeCreate($attributes);

        $model = $this->model->create($attributes);

        $this->afterCreated($model, $attributes);

        return $model;
    }

    /**
     * After created
     *
     * @param Model $model
     * @param array $attributes
     * @return void
     */
    protected function afterCreated(
        Model $model,
        array $attributes
    ) {
    }

    /**
     * Runs before model gets updated
     *
     * @param array $attributes
     * @param Model $model
     * @return void
     */
    protected function beforeUpdate(
        Model $model = null,
        array &$attributes = []
    ) {
    }

    /**
     * Updates a model
     *
     * @param mix $id
     * @param array $attributes
     * @return Model
     */
    public function update($model, array $attributes)
    {
        if (!$model instanceof Model) {
            $model = $this->get($model);
        }

        $this->beforeUpdate($model, $attributes);

        return tap($model, function ($model) use ($attributes) {
            $model->update($attributes);

            $this->afterUpdated($model, $attributes);
        });
    }

    /**
     * Runs after the model is updated
     *
     * @param Model $model
     * @param array $attributes
     * @return void
     */
    protected function afterUpdated(
        Model $model,
        array $attributes
    ) {
    }

    /**
     * Deletes a record
     *
     * @param mix $id
     * @return bool
     */
    public function delete($id)
    {
        if (!$id instanceof Model) {
            $id = $this->get($id);
        }

        return $id->delete();
    }

    /**
     * Guesses the model
     *
     * @return Model
     */
    protected function guessModel(): Model
    {
        if ($this->model) {
            return new $this->model;
        }

        $model = (string) Str::of(class_basename($this))
            ->prepend(config('apix.paths.models'))
            ->replace('Repository', '');

        return new $model;
    }

    protected function getPaginationMethod(?bool $withPages = null)
    {
        if (is_null($withPages)) {
            return config('apix.pagination.with_pages') ? 'paginate' : 'simplePaginate';
        }

        return $withPages ? 'paginate' : 'simplePaginate';
    }
}
