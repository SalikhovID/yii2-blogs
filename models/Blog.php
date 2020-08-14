<?php

namespace salikhovid\blogs\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\web\UploadedFile;
use common\components\behaviors\StatusBehavior;
use common\models\User;
use common\models\ImageManager;

/**
 * This is the model class for table "blog".
 *
 * @property int $id
 * @property string $title
 * @property string|null $text
 * @property string $url
 * @property string $image
 * @property int $status_id
 * @property int $sort
 * @property datetime $date_create
 * @property datetime $date_update
 */
class Blog extends \yii\db\ActiveRecord
{
    const STATUS_LIST = ['off', 'on'];
    public $tags_array;
    public $file;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'blog';
    }

    public function behaviors()
    {
        return [
            'timestampBehavior' => [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'date_create',
                'updatedAtAttribute' => 'date_update',
                'value' => new Expression('NOW()'),
            ],

            'statusBehavior' => [
                'class' => StatusBehavior::className(),
                'statusList' => self::STATUS_LIST
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'url', 'status_id', 'sort'], 'required'],
            [['title'], 'unique'],
            [['text'], 'string'],
            // [['date_update', 'date_create'], 'datetime'],
            [['status_id', 'sort'], 'integer'],
            [['title', 'url'], 'string', 'max' => 150],
            [['file'], 'image'],
            [['image'], 'string', 'max' => 100],
            [['tags_array'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'text' => 'Text',
            'url' => 'Url',
            'file' => 'Image',
            'image' => 'Image',
            'status_id' => 'Status ID',
            'sort' => 'Sort',
            'tags_array' => 'Tags',
            'date_create' => 'Update',
            'date_update' => 'Create',
            'tagsAsString' => 'Tags',
            'author.username' => 'Username',
        ];
    }

    public function getAuthor()
    {
        return $this->hasOne(User::className(),['id'=>'user_id']);
    }

    public function getBlogTag()
    {
        return $this->hasMany(BlogTag::className(),['blog_id'=>'id']);
    }

    public function getTags()
    {
        return $this->hasMany(Tag::className(),['id'=>'tag_id'])->via('blogTag');
    }

    public function getTagsAsString()
    {
        $arr = ArrayHelper::map($this->tags,'id','name');
        return implode(', ', $arr);
    }

    public function getImages()
    {
        return $this->hasMany(ImageManager::ClassName(),['item_id' => 'id'])->andWhere(['class'=>self::tableName()])->orderBy('sort');
    }

    public function getSmallImage()
    {
        if($this->image){
            $path = str_replace('admin/', '', Url::home(true).'uploads/images/blog/50x50/').$this->image;
        }else{
            $path = str_replace('admin/', '', Url::home(true).'uploads/images/no-photo.svg');
        }
        return $path;
    }

    public function getImageLinks()
    {
        return ArrayHelper::getColumn($this->images, 'imageUrl');
    }

    public function getImageLinksData()
    {
        return ArrayHelper::toArray($this->images, [
            ImageManager::className() => [
                'caption' => 'name',
                'key' => 'id',
            ]
        ]);
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->tags_array = $this->tags;
    }

    public function beforeSave($insert)
    {
        if($file = UploadedFile::getInstance($this, 'file')){
            $dir = Yii::getAlias('@frontend').'/web/uploads/images/blog/';
            if(file_exists($dir.$this->image) and $this->image !== null){
                unlink($dir.$this->image);
            }
            if(file_exists($dir.'50x50/'.$this->image) and $this->image !== null){
                unlink($dir.'50x50/'.$this->image);
            }
            if(file_exists($dir.'800/'.$this->image) and $this->image !== null){
                unlink($dir.'800/'.$this->image);
            }

            $this->image = strtotime('now').'_'.Yii::$app->getSecurity()->generateRandomString(6).'.'.$file->extension;
            $file->saveAs($dir.$this->image);
            $imag = Yii::$app->image->load($dir.$this->image);
            $imag->background('fff',0);
            $imag->resize('50','50', Yii\image\drivers\Image::INVERSE);
            $imag->crop('50','50');
            $imag->save($dir.'50x50/'.$this->image,90);
            $imag = Yii::$app->image->load($dir.$this->image);
            $imag->background('fff',0);
            $imag->resize('800', null, Yii\image\drivers\Image::INVERSE);
            $imag->save($dir.'800/'.$this->image,90);
        }
        return parent::beforeSave($insert);
    }

    public function afterSave($insert, $changetAttributes)
    {
        parent::afterSave($insert, $changetAttributes);
        
        if($this->tags_array != $this->tags)
        {
            BlogTag::deleteAll(['blog_id'=>$this->id]);

            if(!empty($this->tags_array))
            {
                foreach ($this->tags_array as $one) {
                    $model = new BlogTag();
                    $model->blog_id = $this->id;
                    $model->tag_id = $one;
                    $model->save();
                }
            }
        }
    }
}
