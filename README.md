# assJSMEQuestion
JSME-Questiontypeplugin for ILIAS 4.3

### Questiontype that allows the creation of molecules ###

Using the free JSME-Editor, the questiontype allows to create molecules with an easy to use interface.

[**JSME-Editor Homepage**](http://peter-ertl.com/jsme/)
[**JSME-Editor Demo**](http://peter-ertl.com/jsme/JSME_2014-06-28/JSME.html)

### Usage ###

Install the plugin

```bash
mkdir -p Customizing/global/plugins/Modules/TestQuestionPool/Questions  
cd Customizing/global/plugins/Modules/TestQuestionPool/Questions
git clone https://github.com/kyro46/assJSMEQuestion.git
```

and activate it in the ILIAS-Admin-GUI. 

Automatic scoring with comparison of SMILE-Code IS implemented, but (due to SMILES-Notation) not valid for molecules with stereo features. Please be cautious.
Activate manual scoring for better control.

### Known Issues ###

* The PDF-generation engine TCPDF in ILIAS can't execute JavaScript, so the question/solution is not shown in the archive file for tests.

### Credits ###
* Development of the plugin-draft for ILIAS 4.3 by Yves Annanias, University Halle, 2014
* Further development by Christoph Jobst, University Halle, 2014/2015/2016