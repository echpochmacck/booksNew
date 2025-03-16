<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property int $role_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $token
 * @property int $gender
 * @property int $age
 *
 * @property Book[] $books
 * @property Gender $gender0
 * @property Progress[] $progresses
 * @property Role $role
 * @property Settings[] $settings
 */
class User extends \yii\db\ActiveRecord  implements IdentityInterface
{

    const SCENARIO_REGISTER = 'register';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email', 'password'], 'required'],
            [['name', 'age'], 'required', 'on' => self::SCENARIO_REGISTER],
            [['email'], 'email', 'on' => self::SCENARIO_REGISTER],
            [['email'], 'unique', 'on' => self::SCENARIO_REGISTER],
            [['age'], 'integer', 'min' => 2, 'max' => 150],
            [['gender'], 'integer', 'min' => 0, 'max' => 1],
            ['password', 'match', 'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])([a-zA-Z\d]){4,}$/', 'on' => self::SCENARIO_REGISTER],
            ['name', 'match', 'pattern' => '/^[A-Z]{1}\w+$/'],
            [['role_id', 'gender'], 'integer'],
            [['name', 'email', 'password', 'token'], 'string', 'max' => 255],
            [['role_id'], 'exist', 'skipOnError' => true, 'targetClass' => Role::class, 'targetAttribute' => ['role_id' => 'id']],
            [['gender'], 'exist', 'skipOnError' => true, 'targetClass' => Gender::class, 'targetAttribute' => ['gender' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'role_id' => 'Role ID',
            'name' => 'Name',
            'email' => 'Email',
            'password' => 'Password',
            'token' => 'Token',
            'gender' => 'Gender',
        ];
    }

    /**
     * Gets query for [[Books]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBooks()
    {
        return $this->hasMany(Book::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Gender0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGender0()
    {
        return $this->hasOne(Gender::class, ['id' => 'gender']);
    }

    /**
     * Gets query for [[Progresses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProgresses()
    {
        return $this->hasMany(Progress::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Role]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::class, ['id' => 'role_id']);
    }

    /**
     * Gets query for [[Settings]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSettings()
    {
        return $this->hasMany(Settings::class, ['user_id' => 'id']);
    }
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['token' => $token]);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        // return $this->authKey;
    }

    public function validateAuthKey($authKey)
    {
        // return $this->authKey === $authKey;
    }
    public function getIsAdmin()
    {
        return $this->role_id == Role::getRoleId('admin');
        // return $this->authKey === $authKey;
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }
}
