<?php

namespace Modules\DataManagement\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
// MODELS
use Illuminate\Support\Facades\Http;
use Modules\DataManagement\Models\Submission;
use Modules\DataManagement\Models\SourceInformation;
use Modules\DataManagement\Models\FamilyInformation;
use Modules\DataManagement\Models\HeadFamily;
use Modules\DataManagement\Models\Interviewwee;
use Modules\DataManagement\Models\Composition;
use Modules\DataManagement\Models\Idp;
use Modules\DataManagement\Models\Returnee;
use Modules\DataManagement\Models\TypeReturnDocumentPhoto;
use Modules\DataManagement\Models\ExtremelyVulnerableMember;
use Modules\DataManagement\Models\AccessCivilDocumentMale;
use Modules\DataManagement\Models\AccessCivilDocumentFemale;
use Modules\DataManagement\Models\HouseLandOwnership;
use Modules\DataManagement\Models\LandOwnershipDocument;
use Modules\DataManagement\Models\HouseCondition;
use Modules\DataManagement\Models\HouseProblemAreaPhoto; 
use Modules\DataManagement\Models\AccessBasicService;
use Modules\DataManagement\Models\FoodConsumptionScore;
use Modules\DataManagement\Models\HouseholdStrategyFood;
use Modules\DataManagement\Models\CommunityAvailability;
use Modules\DataManagement\Models\Livelihood;
use Modules\DataManagement\Models\DurableSolution;
use Modules\DataManagement\Models\SkillIdea;
use Modules\DataManagement\Models\Resettlement;
use Modules\DataManagement\Models\RecentAssistance;
use Modules\DataManagement\Models\InfrasttructureService;
use Modules\DataManagement\Models\PhotoSection;
use Modules\DataManagement\Models\SubmissionStatus;

class ModelHelper
{
    /**
     * Get fillable columns from given models.
     *
     * @param array $models
     * @return array
     */
    public static function getFillableColumns(): array
    {
        $fillable = [
            '_geolocation',
            // 'house_document_photos',
            'house_document_photo_repeat',
            'house_problems_area_photos',
            '_attachments',
            'Please_collect_the_GPS_point',
            '_tags',
            '_notes',
            '_validation_status',
            '_submitted_by',
            'rootUuid',
            'audit',
            'instanceID',
            'instanceName',
            '_xform_id_string',
            '_status'

        ];

        $models = [
            Submission::class,
            SourceInformation::class,
            FamilyInformation::class,
            HeadFamily::class,
            Interviewwee::class,
            Composition::class,
            Idp::class,
            Returnee::class,
            ExtremelyVulnerableMember::class,
            AccessCivilDocumentMale::class,
            AccessCivilDocumentFemale::class,
            HouseLandOwnership::class,
            HouseCondition::class,
            AccessBasicService::class,
            FoodConsumptionScore::class,
            HouseholdStrategyFood::class,
            CommunityAvailability::class,
            Livelihood::class,
            DurableSolution::class,
            SkillIdea::class,
            Resettlement::class,
            RecentAssistance::class,
            InfrasttructureService::class,
            PhotoSection::class,
        ];

        foreach ($models as $model) {
            if (class_exists($model)) {
                $instance = new $model;
                if (method_exists($instance, 'getFillable')) {
                    $fillable = array_merge($fillable, $instance->getFillable());
                }
            }
        }

        return array_values(array_unique($fillable));
    }

    /**
     * Auto-scan app/Models and collect fillables from all models.
     *
     * @return array
     */
    public static function getAllFillableColumns(): array
    {
        $fillable = [];
        $modelPath = app_path('Models');

        if (! File::exists($modelPath)) {
            return [];
        }

        $files = File::allFiles($modelPath);

        foreach ($files as $file) {
            $class = 'App\\Models\\' . $file->getFilenameWithoutExtension();
            if (class_exists($class)) {
                $instance = new $class;
                if (method_exists($instance, 'getFillable')) {
                    $fillable = array_merge($fillable, $instance->getFillable());
                }
            }
        }

        return array_values(array_unique($fillable));
    }
}
