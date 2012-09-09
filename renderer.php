<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Algebra question renderer class.
 *
 * @package    qtype
 * @subpackage algebra
 * @author  Roger Moore <rwmoore 'at' ualberta.ca>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for algebra questions.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_algebra_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {
			global $CFG;

        $question = $qa->get_question();

        $currentanswer = $qa->get_last_qt_var('answer');

        $inputname = $qa->get_qt_field_name('answer');

		$nameprefix = str_replace(':', '_', $inputname); // valid javascript name
        $inputattributes = array(
            'type' => 'text',
            'name' => $inputname,
            'value' => $currentanswer,
            'id' => $inputname,
            'size' => 80,
        );

        if ($options->readonly) {
            $inputattributes['readonly'] = 'readonly';
        }

        $feedbackimg = '';
        if ($options->correctness) {
            $answer = $question->get_matching_answer(array('answer' => $currentanswer));
            if ($answer) {
                $fraction = $answer->fraction;
            } else {
                $fraction = 0;
            }
            $inputattributes['class'] = $this->feedback_class($fraction);
            $feedbackimg = $this->feedback_image($fraction);
        }

		$iframename = $nameprefix.'_if';
		// Name of the javascript function which causes the entered formula to be rendered
		$df_name = $nameprefix.'_display';
        // Create an array of variable names to use when displaying the function entered
        $varnames=array();
        if($question and isset($question->variables)) {
			$variables = $question->variables;
            foreach($question->variables as $var) {
                $varnames[]=$var->name;
            }
        }

        $varnames=implode(',',$varnames);
		// Javascript function which the button uses to display the rendering
		// This function sents the source of the iframe to the 'displayformula.php' script giving
		// it an argument of the formula entered by the student.
		$displayfunction =
			'function '.$df_name."() {\n".
            '    var text="vars='.$varnames.'&expr="+escape(document.getElementsByName("'.$inputname.'")[0].value);'."\n".
			"    if(text.length != 0) {\n".
		    '      document.getElementsByName("'.$iframename.'")[0].src="'.
			$CFG->wwwroot.'/question/type/algebra/displayformula.php?"+'.
			'text.replace(/\+/g,"%2b")'."\n".
			"    }\n".
			"  }\n";

        $questiontext = $question->format_questiontext($qa);

        $input = html_writer::empty_tag('input', $inputattributes) . $feedbackimg;


        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));

        $result .= html_writer::tag('script', $displayfunction, array('type'=>'text/javascript'));
		
        $result .= html_writer::start_tag('div', array('class' => 'ablock'));
		$result .= html_writer::start_tag('div', array('class' => 'prompt', 'style' => 'vertical-align: top'));
		if(isset($question->answerprefix) and !empty($question->answerprefix)) {
              $opts=new StdClass;
              $opts->para=false;
			  $result .= html_writer::tag('div', format_text($question->answerprefix,FORMAT_MOODLE,$opts).$input, array('class' => 'answer'));
        } else {
            $result .= get_string('answer', 'qtype_algebra',
				html_writer::tag('div', $input, array('class' => 'answer')));
        }
		$result .= html_writer::end_tag('div');
        
        $result .= html_writer::end_tag('div');


        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error(array('answer' => $currentanswer)),
                    array('class' => 'validationerror'));
        }
		$result .= html_writer::start_tag('div', array('class' => 'dispresponse'));
		$result .= html_writer::empty_tag('input', array('type'=>'button', 'value'=>'Display Response', 'onclick'=>$df_name.'()'));
		$result .= html_writer::start_tag('iframe', array('name'=>$iframename, 'width'=>'60%', 'height'=>60, 'align'=>'middle', 'src'=>''));
		$result .= html_writer::end_tag('iframe');
		$result .= html_writer::tag('script', $df_name.'();', array('type'=>'text/javascript'));
		$result .= html_writer::end_tag('div');

        return $result;
    }

    public function specific_feedback(question_attempt $qa) {
        $question = $qa->get_question();

        $answer = $question->get_matching_answer(array('answer' => $qa->get_last_qt_var('answer')));
        if (!$answer || !$answer->feedback) {
            return '';
        }

        return $question->format_text($answer->feedback, $answer->feedbackformat,
                $qa, 'question', 'answerfeedback', $answer->id);
    }

    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();

        $answer = $question->get_matching_answer($question->get_correct_response());
        if (!$answer) {
            return '';
        }
        $formatoptions = new stdClass;
        $formatoptions->para = false;
        $formatoptions->clean = false;
        $formattedanswer = format_text($question->formated_expression($answer->answer), FORMAT_MOODLE, $formatoptions);
        return get_string('correctansweris', 'qtype_algebra', s($answer->answer)).  $formattedanswer;
    }
}