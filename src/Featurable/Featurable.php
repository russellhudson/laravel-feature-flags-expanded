<?php

namespace LaravelFeature\Featurable;

use LaravelFeature\Model\Feature as FeatureModel;
use LaravelFeature\Model\Feature;

trait Featurable
{
    public function hasFeature($featureName)
    {
        $model = FeatureModel::where('name', '=', $featureName)->first();

        if ((bool) $model->is_enabled === false) {
            return false;
        }

        $feature = $this->features()->where('name', '=', $featureName)->first();
        return ($feature) ? true : false;
    }

    public function features()
    {
        return $this->morphToMany(Feature::class, 'featurable');
    }
}
