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
        $model = Model::where('name', '=', $feature->getName())->first();

        if (!$model) {
            $model = new Model();
        }

        $model->name = $feature->getName();
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
        $model = Model::where('name', '=', $feature->getName())->first();
        if (!$model) {
            throw new FeatureException('Unable to find the feature.');
        }

        $model->delete();
    }

    public function findByName($featureName)
    {
        /** @var Model $model */
        $model = Model::where('name', '=', $featureName)->first();
        if (!$model) {
            throw new FeatureException('Unable to find the feature.');
        }

        return Feature::fromNameAndStatus(
            $model->name,
            $model->is_enabled
        );
    }

    public function enableFor($featureName, FeaturableInterface $featurable)
    {
        /** @var Model $model */
        $model = Model::where('name', '=', $featureName)->first();

        $featurable->features()->attach($model->id);
    }

    public function disableFor($featureName, FeaturableInterface $featurable)
    {
        /** @var Model $model */
        $model = Model::where('name', '=', $featureName)->first();
//        if (!$model) {
//            throw new FeatureException('Unable to find the feature.');
//        }

        //todo make the above channel agnostic
        if ($featurable->hasFeature($featureName) === false) {
            return;
        }

        $featurable->features()->detach($model->id);
    }

    public function isEnabledFor($featureName, array $args)
    {
        Log::alert('featurable: ' . print_r($args, true));

        /** @var Model $model */
        $model = Model::where('name', '=', $featureName)->first();
        if (empty($model)) {
            return false;
        }

        $i = 0;
        for ($x = 0; $x <= count($args); $x++) {
            if (count($args) > 1) {
                foreach ($args as $featurable) {
                    Log::alert('featurable: ' . print_r($featurable[$i], true));
                    if (array_search($featurable[$i], $args) === $x) {
                        Log::alert('search: ' . print_r($featurable[$i], true));
                        if ($model->is_enabled && $args[$i]->hasFeature($featureName)) {
                            return true;
                        }
                    }
                }
            } else {
                if ($model->is_enabled && $args[$i]->hasFeature($featureName)) {
                    return true;
                }
            }
            $i++;

        }
    }
}
