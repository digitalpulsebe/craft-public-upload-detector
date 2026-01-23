<?php

namespace digitalpulsebe\pud\console\controllers;

use digitalpulsebe\pud\PublicUploadDetector;
use Solspace\Freeform\Freeform;
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

        if (class_exists(Formie::class)) {
            $detections = array_merge($detections, $this->findFormieForms());
        }

        if (class_exists(Freeform::class)) {
            $detections = array_merge($detections, $this->findFreeformForms());
        }

        echo json_encode($detections)."\n";
        return ExitCode::OK;
    }

    protected function findFormieForms()
    {
        $detections = [];

        foreach (Formie::getInstance()->getFields()->getAllFields() as $field) {
            if (str_contains(get_class($field), 'FileUpload')) {
                $parts = explode(':', $field->uploadLocationSource, 2);
                $volumeUid = $parts[1] ?? null;
                $volume = Craft::$app->volumes->getVolumeByUid($volumeUid);
                if ($volume && $volume->getFs() && $volume->getFs()->hasUrls) {
                    $form = $field->getForm();
                    $detections[] = [
                        'field_name' => $field->name,
                        'field_handle' => $field->handle,
                        'form_title' => $form ? $form->title : null,
                        'form_handle' => $form ? $form->handle : null,
                        'volume_name' => $volume->name,
                        'volume_handle' => $volume->handle,
                        'field_uploadLocationSubpath' => $field->uploadLocationSubpath,
                    ];
                }
            }
        }

        return $detections;
    }

    protected function findFreeformForms()
    {
        $detections = [];

        foreach (Freeform::getInstance()->forms->getAllForms() as $form) {
            foreach ($form->getLayout()->getFields() as $field) {
                if (
                    $field instanceof \Solspace\Freeform\Fields\Interfaces\FileUploadInterface
                    && $field instanceof \Solspace\Freeform\Fields\AbstractField
                ) {
                    $selectedAssetSource = $field->getAssetSourceId();
                    $allowed = false; $volume = null;

                    if (!empty($selectedAssetSource)) {
                        $volume = Craft::$app->getVolumes()->getVolumeById($selectedAssetSource);
                        $fileSystem = $volume ? $volume->getFs() : null;

                        if (!empty($volume)
                            && !in_array($volume->handle, PublicUploadDetector::getInstance()->settings->allowedPublicVolumeHandles)
                            && !empty($fileSystem)
                        ) {
                            if (!$fileSystem->hasUrls) {
                                $allowed = true;
                            }
                        }
                    }

                    if (!$allowed) {
                        $detections[] = [
                            'field_name' => $field->getLabel(),
                            'field_handle' => $field->getHandle(),
                            'form_title' => $form->getName(),
                            'form_handle' => $form->getHandle(),
                            'volume_name' => $volume ? $volume->name : '',
                            'volume_handle' => $volume ? $volume->handle : '',
                            'field_uploadLocationSubpath' => $field->getDefaultUploadLocation(),
                        ];
                    }
                }
            }
        }

        return $detections;
    }
}
