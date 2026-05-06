<?php

namespace Nevestul4o\NetworkController\Translation;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Nevestul4o\NetworkController\Models\BaseModel;

class TranslationService
{
    /**
     * If the model and the provided attribute are translatable, the `model_translation` table needs to be joined,
     * along with all translatable columns.
     *
     * @param  BaseModel  $model
     * @param  string  $attributeName
     * @param $builder
     * @return void
     */
    public function joinTranslationModelTableIfNecessary(BaseModel $model, string $attributeName, &$builder): void
    {
        if (!$model->isTranslatable() || !$model->isTranslationAttribute($attributeName)) {
            return;
        }

        $translationTableName = $model->translations()->getRelated()->getTable();

        //check if this table is already joined
        if (!empty($builder->getQuery()->joins)) {
            foreach ($builder->getQuery()->joins as $joinKey => $join) {
                /** @var JoinClause $join */
                if ($join->table === $translationTableName) {
                    //if the model is sluggable and the filtering is for slug, all languages must be used.
                    //therefore, if the translation table has already been joined, remove it and join it again
                    if (
                        !$model->isSlugAble()
                        || $attributeName !== $model->getSlugPropertyName()
                        || empty($join->wheres)
                    ) {
                        return;
                    }
                    unset($builder->getQuery()->joins[$joinKey]);
                    break;
                }
            }
        }

        $selectQueryPart = [$model->getTable() . '.*'];
        foreach ($model->getTranslatedAttributes() as $translatedAttribute) {
            $selectQueryPart[] = $translationTableName . '.' . $translatedAttribute;
        }

        if ($model->isSlugAble() && $attributeName === $model->getSlugPropertyName()) {
            $builder = $builder->leftJoin(
                $translationTableName,
                $model->translations()->getQualifiedForeignKeyName(),
                $model->translations()->getQualifiedParentKeyName()
            )->select($selectQueryPart);

            return;
        }

        $builder = $builder->leftJoin($translationTableName, function ($leftJoin) use ($model) {
            $leftJoin->on(
                $model->translations()->getQualifiedForeignKeyName(),
                $model->translations()->getQualifiedParentKeyName()
            );
            $leftJoin->on(
                'locale',
                DB::raw("'" . app('Astrotomic\Translatable\Locales')->current() . "'")
            );
        })->select($selectQueryPart);
    }
}