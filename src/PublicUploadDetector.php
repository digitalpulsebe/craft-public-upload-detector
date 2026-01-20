<?php

namespace digitalpulsebe\pud;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\ModelEvent;
use digitalpulsebe\pud\models\Settings;
use verbb\formie\elements\Form;
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
        if ($this->getSettings()->restrictFormie) {
            $this->restrictFormieFields();
        }

    }

    protected function restrictFormieFields(): void
    {
        /**
         * Only allow the volume 'forms' to be used as the upload location source for File Upload fields
         */
        Event::on(
            Form::class,
            Form::EVENT_BEFORE_SAVE,
            function(ModelEvent $event) {
                /** @var Form $form */
                $form = $event->sender;

                $fields = $form->getCustomFields();

                foreach ($fields as $field) {
                    // check if the field is a File Upload field
                    if ($field instanceof FileUpload) {
                        $rawSource = $field->uploadLocationSource;

                        if (empty($rawSource)) {
                            continue;
                        }

                        $volumeUid = str_replace('volume:', '', $rawSource);
                        $volume = Craft::$app->getVolumes()->getVolumeByUid($volumeUid);
                        $fileSystem = $volume?->getFs();

                        if (!empty($volume)
                            && !in_array($volume->handle, $this->settings->allowedPublicVolumeHandles)
                            && !empty($fileSystem)
                        ) {
                            if ($fileSystem->hasUrls) {
                                $event->isValid = false;
                                $field->addError('uploadLocationSource', 'Upload location source must be set to private volume');
                            }
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
