<?php

namespace Nevestul4o\NetworkController\Transformers\Traits;

use Nevestul4o\NetworkController\Models\BaseModel;

trait IncludeTranslations
{
    public function addTranslatedAttributes(BaseModel $model, array &$response): void
    {
        if (!$model->displayTranslatedAttributes()) {
            return;
        }

        foreach ($model->getTranslatedAttributes() as $attribute) {
            $response[$attribute] = [];
            foreach (config('translatable.locales') as $locale) {
                $response[$attribute][$locale] = $model->getTranslation($locale)->{$attribute} ?? null;
            }
        }
    }
}