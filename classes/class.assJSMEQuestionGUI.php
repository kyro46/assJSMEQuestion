<?php

/**
 * The assJSMEQuestionGUI class encapsulates the GUI representation
 * for Question-Type-Plugin.
 *
 * @author Yves Annanias <yves.annanias@llz.uni-halle.de>
 * @author Christoph Jobst <cjobst@wifa.uni-leipzig.de>
 * @version	$Id: $
 * @ingroup 	ModulesTestQuestionPool
 *
 * @ilctrl_iscalledby assJSMEQuestionGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilQuestionEditGUI, ilTestExpressPageObjectGUI
 * @ilctrl_calls assJSMEQuestionGUI: ilFormPropertyDispatchGUI
 */
class assJSMEQuestionGUI extends assQuestionGUI
{	
    /**
     * @var assJSMEQuestionPlugin	The plugin object
     */
	var $plugin = null;
	
	/**
	 * @var assJSMEQuestion	The question object
	 */
	public assQuestion $object;
	
	/**
	 * Constructor
	 *
	 * @param integer $id The database id of a question object
	 * @access public
	 */
	public function __construct($id = -1)
	{
	    global $DIC;
	    
	    parent::__construct();
	    
	    /** @var ilComponentFactory $component_factory */
	    $component_factory = $DIC["component.factory"];
	    $this->plugin = $component_factory->getPlugin('assJSMEQuestion');
	    $this->object = new assJSMEQuestion();
	    if ($id >= 0)
	    {
	        $this->object->loadFromDb($id);
	    }
	}

	/**
	 * Creates an output of the edit form for the question
	 *
	 * @param bool $checkonly
	 * @return bool
	 */
	public function editQuestion($checkonly = FALSE)
	{
		$this->initQuestionForm();
		$this->getQuestionTemplate();
		$this->tpl->setVariable("QUESTION_DATA", $this->form->getHTML());
	}
	
	/**
	 * Command: save the question
	 */
	public function save() : void
	{
		// assQuestionGUI::save() 
		// - calls writePostData
		// - redirects after successful saving
		// - otherwise does nothing
		parent::save();
		
		// question couldn't be saved
		$this->form->setValuesByPost();
		$this->getQuestionTemplate();
		$this->tpl->setVariable("QUESTION_DATA", $this->form->getHTML());
	}
	
	/**
	 * Command: save and show page editor
	 */
	public function saveEdit() : void
	{
		// assQuestionGUI::saveEdit() 
		// - calls writePostData
		// - redirects after successful saving
		// - otherwise does nothing
		parent::saveEdit();
		
		// question couldn't be saved
		$this->form->setValuesByPost();
		$this->getQuestionTemplate();
		$this->tpl->setVariable("QUESTION_DATA", $this->form->getHTML());
	}

	/**
	* Creates an output of the edit form for the question
	*
	* @param	boolean		add a new booking to the form
	*/
	private function initQuestionForm()
	{
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->outQuestionType());
		$form->setMultipart(FALSE);
		$form->setTableWidth("100%");
		$form->setId("assJSMEQuestion");

		// title, author, description, question, working time (assessment mode)
		$this->addBasicQuestionFormProperties($form);

		if ($this->object->getId())
		{
			$hidden = new ilHiddenInputGUI("", "ID");
			$hidden->setValue($this->object->getId());
			$form->addItem($hidden);
		}

		// points
		$plugin = $this->object->getPlugin();
		$points = new ilNumberInputGUI($plugin->txt("points"), "points");
		$points->setSize(3);
		$points->setMinValue(0);
		$points->allowDecimals(1);
		$points->setRequired(true);
		$points->setValue($this->object->getPoints());
		$form->addItem($points);
		
		// optionString for the JSME-Applet
		include_once("./Services/Form/classes/class.ilTextInputGUI.php");
		$optionString = new ilTextInputGUI($plugin->txt("optionString"), "optionString");		
		$optionString->setValue($this->object->getOptionString());				
		$optionString->setInfo($plugin->txt("options_hint"));
		$form->addItem($optionString);
		
		// JSME-Applet for sampleSolution
		include_once("./Services/Form/classes/class.ilCustomInputGUI.php");
		$sampleSolution = new ilCustomInputGUI($plugin->txt("sampleSolution"), "sampleSolution");
		$template = $this->getQuestionOutput("", $this->object->getOptionString(), $this->object->getSampleSolution(), $this->object->getSmilesSolution(), $this->object->getSvg());
		$sampleSolution->setHtml($template->get());
		$form->addItem($sampleSolution);												


		$this->populateTaxonomyFormSection($form);
		$this->addQuestionFormCommandButtons($form);
		$this->form = $form;
	}

	/**
	 * Evaluates a posted edit form and writes the form data in the question object
	 *
	 * @param bool $always
	 * @return integer A positive value, if one of the required fields wasn't set, else 0
	 */
	protected function writePostData($always = false): int
	{
		$this->initQuestionForm();
		if ($this->form->checkInput())
		{
            // write the basic data
			$this->writeQuestionGenericPostData();

			$this->object->setPoints((int) $_POST["points"]);
			$this->object->setOptionString($_POST["optionString"]);
			$this->object->setSampleSolution($_POST["sampleSolution"]);
			$this->object->setSmilesSolution($_POST["smilesSolution"]);
			$this->object->setSvg($_POST["svgSolution"]);
			
			// save taxonomy assignment
			$this->saveTaxonomyAssignments();

			// indicator to save the question
			return 0;
		}
		else
		{
			// indicator to show the edit form with errors
			return 1;
		}
	}
	
	/**
	 * Get the HTML output of the question for a test
	 * (this function could be private)
	 *
	 * @param integer $active_id						The active user id
	 * @param integer $pass								The test pass
	 * @param boolean $is_postponed						Question is postponed
	 * @param boolean $use_post_solutions				Use post solutions
	 * @param boolean $show_specific_inline_feedback	Show a specific inline feedback
	 * @return string
	 */
	public function getTestOutput($active_id, $pass = NULL, $is_postponed = FALSE, $use_post_solutions = FALSE, $show_specific_inline_feedback = FALSE): string
	{
		// get the solution of the user for the active pass or from the last pass if allowed
		if (is_null($pass))
		{
		    $pass = ilObjTest::_getPass($active_id);
		}
		
		$user_solution = array();
		$user_solution = $this->object->getSolutionStored($active_id, $pass, null);
		
		if (!is_array($user_solution))
		{
		    $user_solution = array();
		}
		
		$userSampleSolution = $user_solution["value1"];
		$userSmiles = $user_solution["value2"];
		$userSvg = base64_decode($user_solution["value3"]);
		
		$template = $this->getQuestionOutput($this->object->getQuestion(), $this->object->getOptionString(), $userSampleSolution, $userSmiles, $userSvg);
		$questionoutput = $template->get();
		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id, $questionoutput);
		return $pageoutput; 
	}
	
	
	/**
	 * Get the output for preview and test
	 */
	function getQuestionOutput($question, $options, $solution ,$smiles, $svg, $temp="output.html"){
		global $tpl;	
		$plugin       = $this->object->getPlugin();		
		$template     = $plugin->getTemplate($temp);
		$tpl->addJavaScript($plugin->getDirectory().'/templates/jsme/jsme.nocache.js');
		$template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($question, TRUE));		
		$template->setVariable("MOLECULE",$solution);
		$template->setVariable("SMILES",$smiles);
		$template->setVariable("OPTIONS", $options);
		$template->setVariable("SVG", $svg);
		
		return $template;
	}	

	
	/**
	 * Get the output for question preview
	 * (called from ilObjQuestionPoolGUI)
	 *
	 * @param boolean	$show_question_only 	show only the question instead of embedding page (true/false)
	 * @param boolean	$show_question_only
	 * @return string
	 */
	public function getPreview($show_question_only = FALSE, $showInlineFeedback = FALSE)
	{
	    if( is_object($this->getPreviewSession()) )
	    {
	        $solution = $this->getPreviewSession()->getParticipantsSolution();
	    }
	    else
	    {
	        $solution = array('value1' => null, 'value2' => null, 'value3' => null, 'value4' => null);
	    }
	    
		$template = $this->getQuestionOutput($this->object->getQuestion(), $this->object->getOptionString(), "", "", "");		
		
		$questionoutput = $template->get();
		if(!$show_question_only)
		{
		    // get page object output
		    $questionoutput = $this->getILIASPage($questionoutput);
		}
		return $questionoutput;
	}

	/**
	 * Get the question solution output
	 * @param integer $active_id             The active user id
	 * @param integer $pass                  The test pass
	 * @param boolean $graphicalOutput       Show visual feedback for right/wrong answers
	 * @param boolean $result_output         Show the reached points for parts of the question
	 * @param boolean $show_question_only    Show the question without the ILIAS content around
	 * @param boolean $show_feedback         Show the question feedback
	 * @param boolean $show_correct_solution Show the correct solution instead of the user solution
	 * @param boolean $show_manual_scoring   Show specific information for the manual scoring output
	 * @param bool    $show_question_text
	 
	 * @return string solution output of the question as HTML code
	 */
	function getSolutionOutput(
	    $active_id,
	    $pass = NULL,
	    $graphicalOutput = FALSE,
	    $result_output = FALSE,
	    $show_question_only = TRUE,
	    $show_feedback = FALSE,
	    $show_correct_solution = FALSE,
	    $show_manual_scoring = FALSE,
	    $show_question_text = TRUE
	): string
	{
		global $tpl;
		// get the solution of the user for the active pass or from the last pass if allowed
		$user_solution = array();
		if (($active_id > 0) && (!$show_correct_solution))
		{
			// get the solutions of a user
		    $user_solution = $this->object->getSolutionStored($active_id, $pass, true);
		    if (!is_array($user_solution)) 
			{
				$user_solution = array();
			}
		} else {			
		    $user_solution = array(
		        'value1' => null,
		        'value2' => null,
		        'value3' => null,
		        'value4' => null,
		    );
		}						
		
		$userSampleSolution = $user_solution["value1"];
		$userSvg = base64_decode($user_solution["value3"]);

		if($userSvg== '' || $userSvg== null) {
		    $userSvg = $this->object->getPlugin()->txt("old_plugin_solution");
		} else {
		    $userSvg = substr_replace($userSvg, "The PDF engine can't handle inline SVG." . substr($userSvg, -6), -6);
		}

		if($this->object->getSvg()== '' || $this->object->getSvg()== null) {
		    $sampleSvg = $this->object->getPlugin()->txt("old_plugin_question");
		} else {
			$sampleSvg = $this->object->getSvg();
			$sampleSvg = substr_replace($sampleSvg, "The PDF engine can't handle inline SVG." . substr($sampleSvg, -6), -6);
		}

		// generate the question output
		$solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html",TRUE, TRUE, "Modules/TestQuestionPool");
				
		if ($show_correct_solution)
		{			
			//$template = $this->getQuestionOutput("", $this->object->getOptionString(), $this->object->getSampleSolution(), $this->object->getSmilesSolution(), $this->object->getSvg(), "solution.html");		
			$template = $this->getQuestionOutput("", "", "", $this->object->getSmilesSolution(), $sampleSvg, "solution.html");
			$template->setVariable("ID", 'S'.$this->object->getId());
			return $template->get();			
			// hier nur die Musterlösung anzeigen, da wir uns im test beim drücken von check befinden
		}

		//$templateUser = $this->getQuestionOutput($this->object->getQuestion(), $this->object->getOptionString(), $userSampleSolution, $user_solution[0]["value2"], $userSvg, "solution.html");
		$templateUser = $this->getQuestionOutput($this->object->getQuestion(), "", "", $user_solution["value2"], $userSvg, "solution.html");
		$templateUser->setVariable("ID", 'U'.$this->object->getId());
		$questionoutput = $templateUser->get();
		
		if ($show_manual_scoring && strlen($this->object->getSampleSolution()) > 0 )
		{
			//$templateSample = $this->getQuestionOutput($this->object->getPlugin()->txt("sampleSolution"), $this->object->getOptionString(), $this->object->getSampleSolution(), $this->object->getSmilesSolution(), $this->object->getSvg(), "solution.html");
			$templateSample = $this->getQuestionOutput($this->object->getPlugin()->txt("sampleSolution"), "", "", $this->object->getSmilesSolution(), $sampleSvg, "solution.html");
			$templateSample->setVariable("ID", 'S'.$this->object->getId());
			$questionoutput .= "<br>" . $templateSample->get();
		}

		// add the feedback
		$feedback = ($show_feedback && !$this->isTestPresentationContext()) ? $this->getGenericFeedbackOutput($active_id, $pass) : "";
		if (strlen($feedback))
		{
		    $cssClass = ( $this->hasCorrectSolution($active_id, $pass) ?
		        ilAssQuestionFeedback::CSS_CLASS_FEEDBACK_CORRECT : ilAssQuestionFeedback::CSS_CLASS_FEEDBACK_WRONG
		        );
		    
		    $solutiontemplate->setVariable("ILC_FB_CSS_CLASS", $cssClass);
		    $solutiontemplate->setVariable("FEEDBACK", $this->object->prepareTextareaOutput( $feedback, true ));
		    
		}
		$solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);
		
		$solutionoutput = $solutiontemplate->get();
		if(!$show_question_only)
		{
		    // get page object output
		    $solutionoutput = $this->getILIASPage($solutionoutput);
		}
		return $solutionoutput;
	}
	
	/**
	 * Returns the answer specific feedback for the question
	 *
	 * @param array $userSolution Array with the user solutions
	 * @return string HTML Code with the answer specific feedback
	 * @access public
	 */
	public function getSpecificFeedbackOutput($userSolution): string
	{
	    // By default no answer specific feedback is defined
	    $output = '';
	    return $this->object->prepareTextareaOutput($output, TRUE);
	}
	
	
	/**
	 * Sets the ILIAS tabs for this question type
	 * called from ilObjTestGUI and ilObjQuestionPoolGUI
	 */
	public function setQuestionTabs(): void
	{
	    parent::setQuestionTabs();
	}
}
?>