<?php

namespace digitalpulsebe\pud\console\controllers;

use verbb\formie\Formie;
use Craft;
use yii\console\ExitCode;
use yii\helpers\BaseConsole;

class DataRetentionController extends \craft\console\Controller
{
    /**
     * @var bool keep files (Select how to handle file uploads when a submission is deleted)
     */
    public $keep = 'false';

    /**
     * @var int days to keep submissions (After this duration has been met, submissions will be deleted)
     */
    public $days = 90;

    /**
     * Enforce data retention policy on formsubmissions
     */
    public function actionEnforce()
    {
        $days = $this->days;
        $fileUploadsAction = ($this->keep == 'true') ? 'retain' : 'delete';

        $this->stdout("Enforcing data retention policy of ");
        $this->stdout("$days days", BaseConsole::FG_GREEN);
        $this->stdout(" and ");
        $this->stdout("$fileUploadsAction files\n", BaseConsole::FG_GREEN);

        foreach (Formie::getInstance()->getForms()->getAllForms() as $form) {
            $saveChanges = false;

            if ($form->dataRetention != 'days' || $form->dataRetentionValue != $days) {
                $form->dataRetention = 'days';
                $form->dataRetentionValue = $days;
                $saveChanges = true;
            }

            if ($form->fileUploadsAction != $fileUploadsAction) {
                $form->fileUploadsAction = $fileUploadsAction;
                $saveChanges = true;
            }

            if ($saveChanges) {
                $this->stdout(" > Set data retention for form \"$form->title\" ($form->handle)... ");
                Craft::$app->elements->saveElement($form);

                if ($form->hasErrors()) {
                    $this->stdout("failed\n", BaseConsole::FG_RED);

                    $this->stderr(print_r($form->getConsolidatedErrors(), true), BaseConsole::FG_RED);
                } else {
                    $this->stdout("done\n", BaseConsole::FG_GREEN);
                }
            }
        }

        $this->stdout("Finished\n");

        $this->stdout("Run `php craft gc` to run the garbage collector\n");
        return ExitCode::OK;
    }

    public function options($actionID): array
    {
        $options = parent::options($actionID);

        $options[] = 'keep';
        $options[] = 'days';

        return $options;
    }
}
