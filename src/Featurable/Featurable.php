<?php

namespace LaravelFeature\Featurable;

use LaravelFeature\Model\Feature as FeatureModel;
use LaravelFeature\Model\Feature;

trait Featurable
{
    public function hasFeature($featureName)
    {
        $feature = FeatureModel::where('slug', '=', $featureName)
            ->first();

        if (!$feature || !$feature->is_enabled) {
            return false;
        }

        return self::isEnabledForClass('silos', $featureName, $this) ? true : false;
    }

    public static function isEnabledForClass($className, $featureName, $districtId = null)
    {
        /** @var Feature $feature */
        switch ($className) {
            case 'school':
                $class = 'App\\Models\\' . ucfirst(class_basename($className));
                $featurableId = auth()->user()->schools()->pluck('schools.id');
                break;
            case 'schools_districts':
                $class = 'App\\Models\\' . ucfirst(class_basename($className));
                $featurableId = $districtId;
                break;
        }

        if (!isset($class)) {
            return false;
        }

        $escSearch = self::handleBackslash($class);
        $feature = Feature::where('slug', $featureName)->first();
        $featurable = null;
        if ($feature) {
            $featurable = self::where('featurable_id', $featurableId)
                ->where('feature_id', $feature->id)
                ->whereRaw("featurable_type like '%" . $escSearch . "%'")
                ->first();
        }

        return (!empty($featurable) && $featurable->active == 0) ? false : true;
    }

    public static function handleBackslash($value): string
    {
        return str_replace('\\', '\\\\\\\\', $value);
    }

    public function features()
    {
        return $this->morphToMany(Feature::class, 'featurable');
    }
}
