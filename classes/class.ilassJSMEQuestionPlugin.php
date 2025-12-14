<?php
	/**
	* assJSMEQuestion plugin
	*
    * @author Christoph Jobst <iliasplugins.christoph.jobst@outlook.de>
	* @version $Id$
	*
	*/
	class ilassJSMEQuestionPlugin extends ilQuestionsPlugin
	{
	    final function getPluginName(): string
	    {
			return "assJSMEQuestion";
		}
		
		final function getQuestionType(): string
		{
			return "assJSMEQuestion";
		}
		
		final function getQuestionTypeTranslation(): string
		{
			return $this->txt('questionType');
		}
		
		public function uninstall() : bool
		{
		    if (parent::uninstall()) {
		        $this->db->dropTable('il_qpl_qst_jsme_data', false);
		    }
		    return true;
		}
	}
?>