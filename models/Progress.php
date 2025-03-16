<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "progress".
 *
 * @property int $id
 * @property int $progress
 * @property int $user_id
 * @property int $book_id
 *
 * @property Book $book
 * @property User $user
 */
class Progress extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'progress';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['progress'], 'required'],
            [['progress'], 'integer', 'min' => 0, 'max' => 100],
            [['progress', 'user_id', 'book_id'], 'integer'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['book_id'], 'exist', 'skipOnError' => true, 'targetClass' => Book::class, 'targetAttribute' => ['book_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'progress' => 'Progress',
            'user_id' => 'User ID',
            'book_id' => 'Book ID',
        ];
    }

    /**
     * Gets query for [[Book]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBook()
    {
        return $this->hasOne(Book::class, ['id' => 'book_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
