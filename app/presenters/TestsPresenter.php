<?php

namespace App\Presenters;

use App\FormHelper;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

namespace App\Presenters;

class TestsPresenter extends BasePresenter {

  /** @var ActiveRow */
  private $testRow;

  /** @var Selection **/ 
  private $questions;

  public function actionAll () {
  	if (!$this->user->isLoggedIn()) {
  		$this->redirect('Homepage:');
  	}
  }

  public function renderAll () {
  	$this->template->tests = $this->testsRepository->findAll();
  }

  public function actionView ($id) {
    $this->testRow = $this->testsRepository->findById($id);
    
    if (!$this->testRow) {
        $this->error(self::TEST_NOT_FOUND);
    }

    $this['editForm']->setDefaults($this->testRow);
    $this->questions = $this->questionsRepository->findQuestions($this->testRow);
  }

  public function renderView ($id) {
    $this->template->test = $this->testRow;
    $this->template->questions = $this->questions;
  }

  public function submittedAddForm ($form, $values) {
    $this->testsRepository->insert($values);
    $this->flashMessage(self::ITEM_ADD_SUCCESS);
    $this->redirect('all'); 
  }

  public function submittedEditForm ($form, $values) {
    $this->testRow->update($values);
    $this->flashMessage(self::ITEM_EDIT_SUCCESS);
    $this->redirect('all'); 
  }

  public function submittedFinishForm ($form, $values) {
  	$postData = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

    if (isset($postData['url']) && !empty($postData['url'])) {
      $this->redirect('Homepage:'); 
    }

  	$score = $this->evaluateTest($postData);
    $email = array_key_exists('email', $postData) ? $postData['email'] : 'anonym';

    $data = array(
      'test_id' => $this->testRow,
      'email' => $email,
      'score' => $score
    );

    $resultId = $this->resultsRepository->insert($data);
    $this->redirect('Results:view', $resultId);
  }

  protected function createComponentAddForm () {
    $form = new \Nette\Application\UI\Form;
    $form->addText('label', 'Názov');
    $form->addSubmit('save', 'Uložiť');
    $form->onSuccess[] = [$this, 'submittedAddForm'];

    \App\FormHelper::setBootstrapRenderer($form);
    return $form;
  }

  protected function createComponentEditForm () {
    $form = new \Nette\Application\UI\Form;
    $form->addText('label', 'Názov');
    $form->addSubmit('save', 'Uložiť');
    $form->onSuccess[] = [$this, 'submittedEditForm'];

    \App\FormHelper::setBootstrapRenderer($form);
    return $form;
  }

  protected function createComponentFinishForm () {
    $form = new \Nette\Application\UI\Form;
    $form->addText('email', 'E-mail')
      ->addCondition(\Nette\Application\UI\Form::EMAIL, true);
    $form->addText('url', 'Mňam, mňa, toto vyplní len robot')
      ->setAttribute('style', 'opacity: 0; display: inline')
      ->setDefaultValue('');
    $form->addSubmit('finish', 'Ukončiť test');
    $form->onSuccess[] = [$this, 'submittedFinishForm'];

    \App\FormHelper::setBootstrapRenderer($form);
    return $form;
  }

  protected function evaluateTest ($postData) {
  	$earnedPoints = 0;
  	$maxPoints = 0;

  	foreach ($this->questions as $question) {
  		$answer[$question->id] = $question->related('answers')->where('correct', 1)->fetch();
  		$maxPoints += $question->value;
  	}

  	foreach ($this->questions as $question) {
      if (!(array_key_exists('question' . $question->id, $postData))) {
        continue;
      }
  		
      if ((float) $postData['question' . $question->id] === (float) $answer[$question->id]->id) {
  			$earnedPoints += $question->value;
  		}
  	}

  	return round(($earnedPoints / (float) $maxPoints) * 100, 2);
  }
}
