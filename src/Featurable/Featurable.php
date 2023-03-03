<?php

namespace LaravelFeature\Featurable;

use LaravelFeature\Model\Feature as FeatureModel;
use LaravelFeature\Model\Feature;

trait Featurable
{
    public function hasFeature($featureName)
    {
        $feature = FeatureModel::where('slug', '=', $featureName)->first();

        if ((bool) $feature->is_enabled === false) {
            return false;
        }

        return ($feature) ? true : false;
    }

    public function features()
    {
        return $this->morphToMany(Feature::class, 'featurable');
    }
}
