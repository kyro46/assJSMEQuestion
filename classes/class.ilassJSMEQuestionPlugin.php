<?php
	include_once "./Modules/TestQuestionPool/classes/class.ilQuestionsPlugin.php";
	
	/**
	* assJSMEQuestion plugin
	*
	* @author Yves Annanias <yves.annanias@llz.uni-halle.de>
	* @version $Id$
	*
	*/
	class ilassJSMEQuestionPlugin extends ilQuestionsPlugin
	{
		final function getPluginName()
		{
			return "assJSMEQuestion";
		}
		
		final function getQuestionType()
		{
			return "assJSMEQuestion";
		}
		
		final function getQuestionTypeTranslation()
		{
			return $this->txt('questionType');
		}
	}
?>