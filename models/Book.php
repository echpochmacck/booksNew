<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "book".
 *
 * @property int $id
 * @property int $user_id
 * @property int $file_id
 * @property string $title
 * @property string|null $description
 * @property string|null $author
 * @property int $is_public
 *
 * @property File $file
 * @property Progress[] $progresses
 * @property User $user
 */
class Book extends \yii\db\ActiveRecord
{

    public $file = '';
    const SCENARIO_CREATE = 'create';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'book';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['file'], 'required', 'on' => self::SCENARIO_CREATE],
            ['title', 'string', 'max' => 64],
            [['file'], 'file', 'extensions' => 'html', 'checkExtensionByMimeType' => false, 'maxSize' => 512 * 1024],
            [['user_id', 'file_id', 'is_public'], 'integer'],
            [['title', 'description', 'author'], 'string', 'max' => 255],
            [['file_id'], 'exist', 'skipOnError' => true, 'targetClass' => File::class, 'targetAttribute' => ['file_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'file_id' => 'File ID',
            'title' => 'Title',
            'description' => 'Description',
            'author' => 'Author',
            'is_public' => 'Is Public',
        ];
    }

    /**
     * Gets query for [[File]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFile()
    {
        return $this->hasOne(File::class, ['id' => 'file_id']);
    }

    /**
     * Gets query for [[Progresses]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProgresses()
    {
        return $this->hasMany(Progress::class, ['book_id' => 'id']);
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

    public function upload()
    {
        $path = Yii::$app->security->generateRandomString() . ".{$this->file->extension}";
        $this->file->saveAs('books/' . $path);
        return $path;
    }

    public static function getBook(array $data = [])
    {
        $query = Book::find()
            ->select(['book.*', "CONCAT('" . Yii::$app->request->getHostInfo() . "/books/',file.title) as file_url"])
            ->innerJoin('user', 'user.id = book.user_id')
            ->innerJoin('file', 'file.id = book.file_id');
        if (isset($data['progress'])) {
            $query->addSelect(['progress'])
                ->innerJoin('progress', 'progress.book_id = book.id')
            ;
        }
        if (isset($data['in_progress'])) {
            $query->addSelect(['progress'])
                ->innerJoin('progress', 'progress.book_id = book.id')
                ->where('progress > 0 AND progress < 100')
                ->where(['progress.user_id' => $data['user_p_id']])
            ;
        }

        if (isset($data['query'])) {
            $query->filterWhere(['like', 'book.title' => $data['query']]);
            $query->orFilterWhere(['like', 'book.description' => $data['query']]);
        }
        if (isset($data['author'])) {
            $query->filterWhere(['like', 'auhtor' => $data['author']]);
        }


        $query->filterWhere(['book.id' => $data['book_id'] ?? null]);
        $query->filterWhere(['book.user_id' => $data['user_id'] ?? null]);
        $query->filterWhere(['is_public' => $data['is_public'] ?? null]);

        $query->asArray();
        if (isset($data['book_id'])) {
            $res =  $query->one();
            return $res;
        } else {
            $res = $query->all();
            array_map(function ($val) {
                if (isset($val["is_public"])) {
                    $val["is_public"] =  !$val["is_public"]  ?  'false' : 'true';
                }
            }, $res);

            return $res;
        }
    }
}
