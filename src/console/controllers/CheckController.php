<?php

namespace digitalpulsebe\pud\console\controllers;

use verbb\formie\Formie;
use Craft;
use yii\console\ExitCode;

class CheckController extends \craft\console\Controller
{

    /**
     * Check for uploads to public volumes
     */
    public function actionIndex()
    {
        $detections = [];

        foreach (Formie::getInstance()->getFields()->getAllFields() as $field) {
            if (str_contains(get_class($field),'FileUpload')) {
                $volume = Craft::$app->volumes->getVolumeByUid(str_replace('volume:','', $field->uploadLocationSource));
                if ($volume?->getFs()?->hasUrls ?? false) {
                    $form = Formie::getInstance()->getForms()->getFormByUid(str_replace('formie:','', $field->context));
                    $detections[] = [
                        'field_name' => $field->name,
                        'field_handle' => $field->handle,
                        'form_title' => $form->title,
                        'form_handle' => $form->handle,
                        'volume_name' => $volume->name,
                        'volume_handle' => $volume->handle,
                        'field_uploadLocationSubpath' => $field->uploadLocationSubpath,
                    ];
                }
            }
        }

        echo json_encode($detections)."\n";
        return ExitCode::OK;
    }
}
