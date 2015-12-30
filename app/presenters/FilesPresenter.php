<?php

namespace App\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Utils\FileSystem;

class FilesPresenter extends BasePresenter {

    /** @var ActiveRow */
    private $fileRow;

    /** @var string */
    private $error = "File not found!";

    public function actionRemove($id) {
        $this->fileRow = $this->filesRepository->findById($id);
    }

    public function renderRemove($id) {
        if (!$this->fileRow) {
            throw new BadRequestException($this->error);
        }
        $this->template->file = $this->fileRow;
    }

    protected function createComponentRemoveFileForm() {
        $form = new Form;
        $form->addSubmit('remove', 'Odstrániť')
                        ->setAttribute('class', 'btn btn-danger')
                ->onClick[] = $this->submittedFileRemoveForm;
        $form->addSubmit('cancel', 'Zrušiť')
                ->setAttribute('class', 'btn btn-warning')
                ->onClick[] = $this->formCancelled;
        return $form;
    }
    
    public function formCancelled() {
        $this->redirect('Post:show#primary', $this->fileRow->ref('post', 'post_id'));
    }
    
    public function submittedFileRemoveForm() {
        $file = $this->fileRow;
        $fileFile = new FileSystem;
        $fileFile->delete($this->fileFolder . $file->name);
        $id = $file->ref('post', 'post_id');
        $file->delete();
        $this->redirect('Post:show#primary', $id);
    }

}