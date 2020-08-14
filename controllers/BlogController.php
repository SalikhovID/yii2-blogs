<?php

namespace salikhovid\blogs\controllers;

use Yii;
use yii\base\DynamicModel;
use common\modules\blog\models\Blog;
use common\modules\blog\models\Tag;
use common\modules\blog\models\BlogSearch;
use common\models\ImageManager;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\BadRequestHttpException;
use yii\web\UploadedFile;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\FileHelper;

/**
 * BlogController implements the CRUD actions for Blog model.
 */
class BlogController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Blog models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new BlogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Blog model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Blog model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Blog();
        $data = ArrayHelper::map(Tag::find()->all(),'id','name');
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }
        
        return $this->render('create', [
            'data' => $data,
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Blog model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $data = ArrayHelper::map(Tag::find()->all(),'id','name');

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'data' => $data,
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Blog model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Blog model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Blog the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Blog::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionImageUpload($sub = 'main')
    {
        $this->enableCsrfValidation = false;
        if(Yii::$app->request->isPost){
            $dir = Yii::getAlias('@frontend').'/web/uploads/images/'.$sub.'/';
            if(!file_exists($dir)){
                FileHelper::createDirectory($dir);
            }
            $result_link = str_replace('admin/', '', Url::home(true).'uploads/images/'.$sub.'/');
            $file = UploadedFile::getInstanceByName('file');
            $model = new DynamicModel(compact('file'));
            $model->addRule('file','image')->validate();

            if($model->hasErrors()){
                $result = ['error' => $model->getFirstError('file')];
            }else{
                $fileName = strtotime('now').'_'.Yii::$app->getSecurity()->generateRandomString(6).'.'.$model->file->extension;
                if($model->file->saveAs($dir.$fileName)){
                    // $imag = Yii::$app->image->load($dir.$fileName);
                    // $imag->resize(800, NULL, Yii\image\drivers\Image::PRECISE)->save($dir.$fileName,85);
                    $result = ['filelink'=>$result_link.$fileName, 'filename'=>$fileName];
                }else{
                    $result = ['error' => Yii::t('vova07/imperavi', 'ERROR_CAN_NOT_UPLOAD_FILE')];
                }
            }

            Yii::$app->response->format = Response::FORMAT_JSON;
            return $result;
        }else{
            throw new BadRequestHttpException("Only POST is allowed");
        }
    }

    public function actionSaveImage()
    {
        $this->enableCsrfValidation = false;
        if(Yii::$app->request->isPost){
            $post = Yii::$app->request->post();
            $dir = Yii::getAlias('@frontend').'/web/uploads/images/'.$post['ImageManager']['class'].'/';
            if(!file_exists($dir)){
                FileHelper::createDirectory($dir);
            }
            $result_link = str_replace('admin/', '', Url::home(true).'uploads/images/'.$post['ImageManager']['class'].'/');
            $file = UploadedFile::getInstanceByName('ImageManager[attachment][0]');
            $model = new ImageManager();
            $model->item_id = $post['ImageManager']['item_id'];
            $model->class = $post['ImageManager']['class'];
            $model->name = strtotime('now').'_'.Yii::$app->getSecurity()->generateRandomString(6).'.'.$file->extension;
            $model->validate();

            if($model->hasErrors()){
                FileHelper::createDirectory($dir.gettype($model->item_id));
                $result = ['error' => $model->getFirstError('file')];
            }else{
                if($file->saveAs($dir.$model->name)){
                    $imag = Yii::$app->image->load($dir.$model->name);
                    $imag->resize(800, NULL, Yii\image\drivers\Image::PRECISE)->save($dir.$model->name,85);
                    $result = ['filelink'=>$result_link.$model->name, 'filename'=>$model->name];
                }else{
                    $result = ['error' => 'ERROR'];
                }
                $model->save();
            }

            Yii::$app->response->format = Response::FORMAT_JSON;
            return $result;
        }else{
            throw new BadRequestHttpException("Only POST is allowed");
        }
    }


    public function actionDeleteImage()
    {
        if($model = ImageManager::findOne(Yii::$app->request->post('key')) and $model->delete()){
            return true;
        }else{
            throw new NotFoundHttpException('The requested page does not exist.');
            
        }
    }

    public function actionSortImage($id)
    {
        if(Yii::$app->request->isAjax){
            $post = Yii::$app->request->post('sort');
            if($post['oldIndex'] > $post['newIndex']){
                $param = ['and', ['>=', 'sort', $post['newIndex']],['<','sort',$post['oldIndex']]];
                $counter = 1;
            }else{
                $param = ['and', ['<=', 'sort', $post['newIndex']],['>','sort',$post['oldIndex']]];
                $counter = -1;
            }

            ImageManager::updateAllCounters(['sort' => $counter],[
                'and', ['class' => 'blog', 'item_id' => $id], $param
            ]);

            ImageManager::updateAll(['sort' => $counter],[
                'id' => $post['stack'][$post['newIndex']]['key']
            ]);
            return true;

        }
        throw new MethodNotAllowedHttpException();
        
    }
    
}
