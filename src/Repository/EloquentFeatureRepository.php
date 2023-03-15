<?php

namespace LaravelFeature\Repository;

use Honeybadger\Honeybadger;
use Illuminate\Support\Facades\Log;
use LaravelFeature\Domain\Exception\FeatureException;
use LaravelFeature\Domain\Repository\FeatureRepositoryInterface;
use LaravelFeature\Domain\Model\Feature;
use LaravelFeature\Featurable\FeaturableInterface;
use LaravelFeature\Model\Feature as Model;


class EloquentFeatureRepository implements FeatureRepositoryInterface
{
    public function save(Feature $feature)
    {
        /** @var Model $model */
        $model = Model::where('slug', '=', $feature->getName())->first();

        if (!$model) {
            $model = new Model();
        }

        $model->slug = $feature->getName();
        $model->is_enabled = $feature->isEnabled();

        try {
            $model->save();
        } catch (\Exception $e) {
            throw new FeatureException('Unable to save the feature: ' . $e->getMessage());
        }
    }

    public function remove(Feature $feature)
    {
        /** @var Model $model */
        $model = Model::where('slug', '=', $feature->getName())->first();
        if (!$model) {
//            return;
            throw new FeatureException('Unable to find the feature.');
        }

        $model->delete();
    }

    public function findByName($featureName)
    {
        /** @var Model $model */
        $model = Model::where('slug', '=', $featureName)->first();
        if (!$model) {
            return;
        }

        return Feature::fromNameAndStatus(
            $model->slug,
            $model->is_enabled
        );
    }

    public function enableFor($featureName, FeaturableInterface $featurable)
    {
        /** @var Model $model */
        $model = Model::where('slug', '=', $featureName)->first();

        $featurable->features()->attach($model->id);
    }

    public function disableFor($featureName, FeaturableInterface $featurable)
    {
        $model = Model::where('slug', '=', $featureName)->first();

        if (class_exists(FeaturableTable::class)) {
            $thing = FeaturableTable::where('featurable_id', $featurable->id)
            ->where('feature_id', $model->id)
            ->whereRaw("featurable_type like '%".$this->handle_backslash(get_class($featurable))."%'")
            ->get();

            if ($thing->isEmpty()) {
                $model = Model::where('slug', '=', $featureName)->first();

                $featurable->features()->attach($model->id);

                return;
            }
        }
        $featurable->features()->detach($model->id);
    }

    /**
     * @throws FeatureException
     */
    public function isEnabledFor($featureName, $args)
    {
        if (empty($args) || $featureName === '') {
            Log::alert('Arguments: ' . print_r($args, true));
            throw new FeatureException('You are missing
            arguments or have an error in the featurables table');
        }


        /** @var Model $model */
        $model = Model::where('slug', '=', $featureName)->first();
        if (empty($model)) {
            return false;
        }

        if (!is_array($args)) {
            if ($model->is_enabled && $args->hasFeature($featureName)) {
                return true;
            }
        } else {
            foreach ($args as $featurable) {
                if ($model->is_enabled && $featurable->hasFeature($featureName)) {
                    return true;
                }
            }

        }
        return false;
    }

    public static function handle_backslash($value): string
    {
        return str_replace('\\', '\\\\\\\\', $value);
    }
}
