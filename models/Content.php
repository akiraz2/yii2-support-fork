<?php
/**
 * @author Harry Tang <harry@powerkernel.com>
 * @link https://powerkernel.com
 * @copyright Copyright (c) 2017 Power Kernel
 */

namespace powerkernel\support\models;

use powerkernel\support\Mailer;
use powerkernel\support\traits\ModuleTrait;
use Yii;

/**
 * This is the model class for table "ticket_content".
 *
 * @property integer|\MongoDB\BSON\ObjectID|string $id
 * @property integer|\MongoDB\BSON\ObjectID|string $id_ticket
 * @property string $content
 * @property integer|\MongoDB\BSON\ObjectID|string $created_by
 * @property integer|\MongoDB\BSON\UTCDateTime $created_at
 * @property integer|\MongoDB\BSON\UTCDateTime $updated_at
 *
 * @property Account $createdBy
 * @property Ticket $ticket
 */
class Content extends ContentBase
{
    use ModuleTrait;

    const STATUS_ACTIVE = 10;
    const STATUS_INACTIVE = 20;

    /**
     * get status text
     * @return string
     */
    public function getStatusText()
    {
        $status = $this->status;
        $list = self::getStatusOption();
        if (!empty($status) && in_array($status, array_keys($list))) {
            return $list[$status];
        }
        return \powerkernel\support\Module::t('support', 'Unknown');
    }

    /**
     * get status list
     * @param null $e
     * @return array
     */
    public static function getStatusOption($e = null)
    {
        $option = [
            self::STATUS_ACTIVE => \powerkernel\support\Module::t('support', 'Active'),
            self::STATUS_INACTIVE => \powerkernel\support\Module::t('support', 'Inactive'),
        ];
        if (is_array($e)) {
            foreach ($e as $i) {
                unset($option[$i]);
            }
        }
        return $option;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id_ticket', 'content'], 'required'],
            [['content'], 'string'],
            [
                ['created_by'],
                'exist',
                'skipOnError' => true,
                'targetClass' => $this->getModule()->userModel,
                'targetAttribute' => ['created_by' => $this->getModule()->userPK]
            ],
            [
                ['id_ticket'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Ticket::className(),
                'targetAttribute' => ['id_ticket' => $this->getModule()->isMongoDb() ? '_id' : 'id']
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => \powerkernel\support\Module::t('support', 'ID'),
            'id_ticket' => \powerkernel\support\Module::t('support', 'Id Ticket'),
            'content' => \powerkernel\support\Module::t('support', 'Content'),
            'created_by' => \powerkernel\support\Module::t('support', 'Created By'),
            'created_at' => \powerkernel\support\Module::t('support', 'Created At'),
            'updated_at' => \powerkernel\support\Module::t('support', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQueryInterface
     */
    public function getCreatedBy()
    {
        return $this->hasOne($this->getModule()->userModel, [$this->getModule()->userPK => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQueryInterface
     */
    public function getTicket()
    {
        if (is_a($this, '\yii\mongodb\ActiveRecord')) {
            return $this->hasOne(Ticket::className(), ['_id' => 'id_ticket']);
        } else {
            return $this->hasOne(Ticket::className(), ['id' => 'id_ticket']);
        }

    }

    /**
     * @inheritdoc
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes); // TODO: Change the autogenerated stub
        if ($insert) {

            if ($this->created_by != $this->ticket->created_by) {
                $email = $this->ticket->createdBy->{\Yii::$app->getModule('support')->userEmail};
                Yii::$app->language = $this->ticket->createdBy->language;
            } else {
                $email = Yii::$app->params['adminEmail'];
            }
            if ($this->getModule()->notifyByEmail) {
                /* send email */
                $subject = \powerkernel\support\Module::t('support', '[{APP} Ticket #{ID}] Re: {TITLE}',
                    ['APP' => Yii::$app->name, 'ID' => $this->ticket->id, 'TITLE' => $this->ticket->title]);
                $this->mailer->sendMessage(
                    $email,
                    $subject,
                    [
                        'html' => 'reply-ticket-html',
                        'text' => 'reply-ticket-text'
                    ],
                    ['title' => $subject, 'model' => $this]
                );
            }
        }
    }

    protected function getMailer()
    {
        return \Yii::$container->get(Mailer::className());
    }
}
