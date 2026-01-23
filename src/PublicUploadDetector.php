<?php

namespace digitalpulsebe\pud;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\ModelEvent;
use digitalpulsebe\pud\models\Settings;
use Solspace\Freeform\controllers\api\FormsController;
use Solspace\Freeform\Events\Forms\PersistFormEvent;
use Solspace\Freeform\Form\Form as FreeformForm;
use verbb\formie\elements\Form as FormieForm;
use verbb\formie\fields\formfields\FileUpload;
use yii\base\Event;

/**
 * Public Upload Detector plugin
 *
 * @method static PublicUploadDetector getInstance()
 * @property Settings $settings
 * @method Settings getSettings()
 * @author Digital Pulse NV <support@digitalpulse.be>
 * @copyright Digital Pulse NV
 * @license MIT
 */
class PublicUploadDetector extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = false;

    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function() {
        });
    }

    private function attachEventHandlers(): void
    {
        if ($this->getSettings()->restrictFormie && class_exists(FormieForm::class)) {
            $this->restrictFormieFields();
        }
        if ($this->getSettings()->restrictFreeform && class_exists(FreeformForm::class)) {
            $this->restrictFreeformFields();
        }

    }

    protected function restrictFormieFields(): void
    {
        Event::on(
            FormieForm::class,
            FormieForm::EVENT_BEFORE_SAVE,
            function(ModelEvent $event) {
                /** @var Form $form */
                $form = $event->sender;

                $fields = $form->getCustomFields();

                foreach ($fields as $field) {
                    // check if the field is a File Upload field
                    if ($field instanceof FileUpload) {
                        $allowed = false;
                        $parts = explode(':', $field->uploadLocationSource, 2);
                        $volumeUid = $parts[1] ?? null;
                        $volume = $volumeUid ? Craft::$app->volumes->getVolumeByUid($volumeUid) : null;

                        if (
                            $volume
                            && $volume->getFs()
                            && !$volume->getFs()->hasUrls
                        ) {
                            $allowed = true;
                        }

                        if (in_array($volume->handle, $this->settings->allowedPublicVolumeHandles)) {
                            $allowed = true;
                        }

                        if (!$allowed) {
                            $event->isValid = false;
                            $field->addError('uploadLocationSource', 'Upload location source must be set to private volume');
                        }
                    }
                }
            });
    }

    protected function restrictFreeformFields(): void
    {
        Event::on(
            FormsController::class,
            FormsController::EVENT_UPDATE_FORM,
            function (PersistFormEvent $event) {
                $fields = $event->getPayload()->layout->fields;

                foreach ($fields as $field) {
                    if (
                        str_contains($field->typeClass, 'FileUpload')
                        || str_contains($field->typeClass, 'FileDragAndDropField')
                    ) {
                        $selectedAssetSource = $field->properties->assetSourceId;
                        $allowed = false;

                        if (!empty($selectedAssetSource)) {
                            $volume = Craft::$app->getVolumes()->getVolumeById($selectedAssetSource);
                            $fileSystem = $volume ? $volume->getFs() : null;

                            if (!empty($volume)
                                && !in_array($volume->handle, $this->settings->allowedPublicVolumeHandles)
                                && !empty($fileSystem)
                            ) {
                                if (!$fileSystem->hasUrls) {
                                    $allowed = true;
                                }
                            }
                        }

                        if (!$allowed) {
                            $event->addErrorsToResponse(
                                'fields',
                                [$field->uid => ['assetSourceId' => ['Upload location source must be set to private volume']]]
                            );
                        }

                    }
                }
            });
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }
}
