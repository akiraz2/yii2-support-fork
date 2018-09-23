<?php
/**
 * @author Harry Tang <harry@powerkernel.com>
 * @link https://powerkernel.com
 * @copyright Copyright (c) 2016 Power Kernel
 */

namespace powerkernel\support\components;

use powerkernel\support\traits\ModuleTrait;
use Yii;
use yii\base\ActionEvent;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'backend' => [
 *             'class' => common\components\BackendFilter::class,
 *             'actions' => [
 *                 'index',
 *                 'view',
 *                 'create',
 *                 'update',
 *                 'delete'
 *             ],
 *         ],
 *     ];
 * }
 * ```
 */

/**
 * Class BackendFilter *
 */
class BackendFilter extends Behavior
{
    use ModuleTrait;

    public $actions = [];

    /**
     * Declares event handlers for the [[owner]]'s events.
     * @return array events (array keys) and the corresponding event handler methods (array values).
     */
    public function events()
    {
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }

    /**
     * @param ActionEvent $event
     * @return bool
     * @throws NotFoundHttpException when the request method is not allowed.
     */
    public function beforeAction($event)
    {
        $action = $event->action->id;
        if (in_array($action, $this->actions) or in_array('*', $this->actions)) {
            if (Yii::$app->id != $this->getModule()->appBackendId) {
                $event->isValid = false;
                throw new NotFoundHttpException('The requested page does not exist.');
            }
        }
        return $event->isValid;
    }
}